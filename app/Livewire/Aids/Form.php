<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\AssertAidTypeMatchesProgram;
use App\Actions\Aids\CreateAid;
use App\Actions\Aids\SubmitAid;
use App\Actions\Aids\UpdateAid;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\BeneficiaryStatus;
use App\Enums\RecurrenceFrequency;
use App\Exceptions\InvalidAidTransitionException;
use App\Imports\SpreadsheetReader;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use App\Models\RecurringAidPlan;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Aid create/edit form. Editing an existing aid is only reachable while it
 * is still a draft: AidPolicy::update() denies the ability entirely once
 * the aid has left that status, so Gate::authorize() below already turns
 * into a 403 in that case.
 */
class Form extends Component
{
    use WithFileUploads;

    /**
     * Safety cap for {@see selectAllMatching()}: the maximum number of
     * beneficiaries a single "select all" click can add to
     * {@see $beneficiary_ids} at once, whether the search box is empty
     * (matches the whole beneficiary table) or filled in. Chosen well above
     * any realistic single bulk-aid batch while still keeping the request
     * and the resulting confirmation/chip list bounded.
     */
    private const int MAX_SELECT_ALL = 200;

    /**
     * Private disk the uploaded national-id workbook is parked on while we
     * read it: such lists carry national ids and must never touch the public
     * disk. Mirrors {@see BatchCreate}.
     */
    private const string DISK = 'local';

    public ?Aid $aid = null;

    /**
     * Single beneficiary, used only in edit mode (an existing aid always
     * belongs to exactly one beneficiary).
     */
    public ?int $beneficiary_id = null;

    /**
     * Selected beneficiary ids, used only in create mode: submitting the
     * form raises one independent aid per id, all sharing the same
     * program/type/amount-or-items/notes entered below.
     *
     * @var array<int, int>
     */
    public array $beneficiary_ids = [];

    /**
     * Create-mode batch conveniences (ported from
     * {@see BatchCreate}). Selected beneficiary categories:
     * ticking a category merges every eligible beneficiary in it into
     * {@see $beneficiary_ids} (never removing an already-chosen id).
     *
     * @var array<int, int>
     */
    public array $category_ids = [];

    /**
     * Optional Excel/CSV of national ids to bulk-match into
     * {@see $beneficiary_ids} (create mode only).
     */
    public mixed $nationalIdFile = null;

    /**
     * National ids from the last upload that matched no eligible beneficiary.
     *
     * @var array<int, string>
     */
    public array $unmatchedNationalIds = [];

    /**
     * Per-beneficiary cash amount override, keyed by beneficiary id. A blank
     * entry falls back to the form's {@see $amount} at creation time. Only
     * used for cash aids in create mode.
     *
     * @var array<int, mixed>
     */
    public array $overrideAmounts = [];

    public ?int $aid_program_id = null;

    /**
     * Optional human label for the aid (e.g. "Rent payment — July"), shown in
     * the aid header and lists. Passed through to {@see CreateAid} on create
     * and written onto the aid on update.
     */
    public ?string $title = null;

    public string $type = 'cash';

    public ?float $amount = null;

    public string $purpose = '';

    public string $notes = '';

    /** @var array<int, array{name: string, quantity: int, estimated_value: ?float, description: string}> */
    public array $items = [];

    /**
     * Free-text query for the beneficiary picker below, added in phase 3b
     * so the form doesn't have to load every beneficiary into the page.
     */
    public string $beneficiarySearch = '';

    /**
     * Whether the "confirm before submitting for approval" modal is open —
     * opened by {@see confirmSubmit()} once the form validates.
     */
    public bool $showSubmitConfirm = false;

    /**
     * Phase 10 recurrence controls. When {@see $isRecurring} is on, saving
     * the aid also creates/updates a {@see RecurringAidPlan} tied to it.
     */
    public bool $isRecurring = false;

    public string $recurrenceFrequency = 'monthly';

    public ?int $recurrenceIntervalMonths = null;

    public ?string $recurrenceStartsOn = null;

    /**
     * First due (entitlement) date for the schedule, distinct from
     * {@see $recurrenceStartsOn}. When left blank the plan falls back to the
     * start date for its first cycle.
     */
    public ?string $recurrenceDueOn = null;

    /**
     * Optional title template applied to every aid this plan generates
     * (see {@see RecurringAidPlan::renderTitle()} for its placeholders).
     */
    public ?string $recurrenceTitleTemplate = null;

    public ?string $recurrenceEndsOn = null;

    /**
     * How many days before each due date the next cycle's aid is generated
     * (0 = generate exactly on the due date).
     */
    public int $recurrenceLeadDays = 0;

    public bool $recurrenceActive = true;

    /**
     * Phase 9 (aid-form part): pending supporting-document uploads. Each
     * entry is a {@see TemporaryUploadedFile} that is moved into the aid's
     * private 'aid_documents' media collection on save.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $documents = [];

    public function mount(?Aid $aid = null): void
    {
        $this->aid = $aid;

        Gate::authorize(
            $this->aid?->exists ? 'update' : 'create',
            $this->aid ?? Aid::class,
        );

        if (! $this->aid?->exists) {
            return;
        }

        $this->beneficiary_id = $this->aid->beneficiary_id;
        $this->aid_program_id = $this->aid->aid_program_id;
        $this->title = $this->aid->title;
        $this->type = $this->aid->type->value;
        $this->amount = $this->aid->amount !== null ? (float) $this->aid->amount : null;
        $this->purpose = (string) $this->aid->purpose;
        $this->notes = (string) $this->aid->notes;
        $this->items = $this->aid->items->map(fn ($item): array => [
            'name' => $item->name,
            'quantity' => $item->quantity,
            'estimated_value' => $item->estimated_value !== null ? (float) $item->estimated_value : null,
            'description' => (string) $item->description,
        ])->all();

        if ($plan = $this->aid->recurringPlan) {
            $this->isRecurring = true;
            $this->recurrenceFrequency = $plan->frequency->value;
            $this->recurrenceIntervalMonths = $plan->interval_months;
            $this->recurrenceStartsOn = $plan->starts_on?->toDateString();
            $this->recurrenceDueOn = $plan->due_on?->toDateString();
            $this->recurrenceTitleTemplate = $plan->title_template;
            $this->recurrenceEndsOn = $plan->ends_on?->toDateString();
            $this->recurrenceLeadDays = $plan->lead_days;
            $this->recurrenceActive = $plan->is_active;
        }
    }

    /**
     * Active aid programs available to raise an aid against.
     *
     * @return Collection<int, AidProgram>
     */
    #[Computed]
    public function programs(): Collection
    {
        return AidProgram::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Active beneficiary categories offered as checkbox chips in create mode:
     * ticking one merges every eligible beneficiary in it into the selection.
     *
     * @return Collection<int, BeneficiaryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BeneficiaryCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * When recurrence is switched on, offer the default title template if the
     * field is still blank — so a recurring aid always gets a meaningful title
     * out of the box, while leaving any template the user already typed alone.
     */
    public function updatedIsRecurring(bool $value): void
    {
        if ($value && ($this->recurrenceTitleTemplate === null || trim($this->recurrenceTitleTemplate) === '')) {
            $this->recurrenceTitleTemplate = RecurringAidPlan::DEFAULT_TITLE_TEMPLATE;
        }
    }

    /**
     * Fill the title-template field with the default template (button on the
     * form).
     */
    public function useDefaultTitleTemplate(): void
    {
        $this->recurrenceTitleTemplate = RecurringAidPlan::DEFAULT_TITLE_TEMPLATE;
    }

    /**
     * A live preview of the title each generated aid will carry, rendered from
     * the current template + the selected program/first beneficiary + the due
     * (or start) date, for the first cycle. Null when there is nothing to show.
     */
    #[Computed]
    public function titlePreview(): ?string
    {
        if (! $this->isRecurring || $this->recurrenceTitleTemplate === null || trim($this->recurrenceTitleTemplate) === '') {
            return null;
        }

        $program = $this->aid_program_id
            ? $this->programs->firstWhere('id', $this->aid_program_id)?->name
            : null;

        $firstBeneficiaryId = $this->beneficiary_id ?: ($this->beneficiary_ids[0] ?? null);
        $beneficiary = $firstBeneficiaryId
            ? Beneficiary::query()->find($firstBeneficiaryId)?->short_name
            : null;

        $anchor = $this->recurrenceDueOn ?: $this->recurrenceStartsOn;

        try {
            $due = $anchor ? CarbonImmutable::parse($anchor) : CarbonImmutable::now();
        } catch (\Exception) {
            $due = CarbonImmutable::now();
        }

        return RecurringAidPlan::renderTitleTemplate(
            $this->recurrenceTitleTemplate,
            $program,
            $beneficiary,
            $due,
            1,
        );
    }

    /**
     * The first 20 beneficiaries matching {@see $beneficiarySearch} by name
     * or national ID, always including every currently selected beneficiary
     * (the single {@see $beneficiary_id} in edit mode, or the whole
     * {@see $beneficiary_ids} list in create mode) so none of them ever
     * disappears from the picker once chosen.
     *
     * @return Collection<int, Beneficiary>
     */
    #[Computed]
    public function beneficiaries(): Collection
    {
        $matches = $this->matchingBeneficiariesQuery()
            ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'national_id'])
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        $selectedIds = array_unique(array_merge(
            $this->beneficiary_id ? [$this->beneficiary_id] : [],
            $this->beneficiary_ids,
        ));

        $missingIds = array_diff($selectedIds, $matches->pluck('id')->all());

        if ($missingIds !== []) {
            $missing = Beneficiary::query()
                ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'national_id'])
                ->whereIn('id', $missingIds)
                ->get();

            $matches = $missing->concat($matches);
        }

        return $matches;
    }

    /**
     * Beneficiaries matching {@see $beneficiarySearch} by name or national
     * ID (unfiltered/unlimited base query), shared by {@see beneficiaries()}
     * (which layers its own column selection, ordering and 20-row page
     * limit on top) and {@see selectAllMatching()} (which layers its own
     * {@see MAX_SELECT_ALL} cap instead).
     */
    private function matchingBeneficiariesQuery(): Builder
    {
        return Beneficiary::query()
            ->when($this->beneficiarySearch !== '', function (Builder $query): void {
                $term = "%{$this->beneficiarySearch}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('second_name', 'like', $term)
                        ->orWhere('third_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term);
                });
            });
    }

    /**
     * The full beneficiary models behind {@see $beneficiary_ids}, in
     * selection order, for rendering them as removable chips in the
     * create-mode picker.
     *
     * @return Collection<int, Beneficiary>
     */
    #[Computed]
    public function selectedBeneficiaries(): Collection
    {
        if ($this->beneficiary_ids === []) {
            return collect();
        }

        $byId = Beneficiary::query()
            ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'national_id'])
            ->whereIn('id', $this->beneficiary_ids)
            ->get()
            ->keyBy('id');

        return collect($this->beneficiary_ids)
            ->map(fn (int $id): ?Beneficiary => $byId->get($id))
            ->filter()
            ->values();
    }

    /**
     * Add a beneficiary to the create-mode multi-select, ignoring
     * duplicates. The search box and its results list are deliberately
     * left untouched (rather than cleared) so several beneficiaries can be
     * added one after another from the same search without retyping.
     */
    public function addBeneficiary(int $id): void
    {
        if (! in_array($id, $this->beneficiary_ids, true)) {
            $this->beneficiary_ids[] = $id;
        }
    }

    public function removeBeneficiary(int $id): void
    {
        $this->beneficiary_ids = array_values(array_diff($this->beneficiary_ids, [$id]));
    }

    /**
     * Add every beneficiary matching {@see $beneficiarySearch} (or, when the
     * search box is empty, every beneficiary) to the create-mode
     * multi-select in one go, merging with whatever was already selected
     * and never adding a duplicate. Capped at {@see MAX_SELECT_ALL} rows per
     * click for safety; a toast tells the user when the match count exceeds
     * that cap so the picker never silently drops the tail of a very large
     * result set.
     */
    public function selectAllMatching(): void
    {
        $query = $this->matchingBeneficiariesQuery();

        $totalMatching = (clone $query)->count();

        $matchingIds = (clone $query)
            ->orderBy('first_name')
            ->limit(self::MAX_SELECT_ALL)
            ->pluck('id')
            ->all();

        $this->beneficiary_ids = array_values(array_unique([
            ...$this->beneficiary_ids,
            ...$matchingIds,
        ]));

        if ($totalMatching > self::MAX_SELECT_ALL) {
            $this->dispatch('toast', type: 'info', message: __('aids.select_all_capped', ['count' => self::MAX_SELECT_ALL]));
        }
    }

    /**
     * Empty the create-mode multi-select entirely.
     */
    public function clearSelection(): void
    {
        $this->beneficiary_ids = [];
    }

    /**
     * Eligible beneficiaries for category/Excel selection: everyone except the
     * genuinely-excluded states. Mirrors
     * {@see BatchCreate::activeBeneficiariesQuery()} so the
     * two screens gather the same population.
     */
    private function eligibleBeneficiariesQuery(): Builder
    {
        return Beneficiary::query()
            ->whereNotIn('status', [
                BeneficiaryStatus::Suspended->value,
                BeneficiaryStatus::Deactivated->value,
                BeneficiaryStatus::Rejected->value,
            ]);
    }

    /**
     * Merge a set of beneficiary ids into {@see $beneficiary_ids}, normalising
     * to unique ints (DOM checkbox values arrive as strings) and never adding a
     * duplicate. Returns the number of ids actually added.
     *
     * @param  array<int, int|string>  $ids
     */
    private function mergeBeneficiaryIds(array $ids): int
    {
        $before = count($this->beneficiary_ids);

        $this->beneficiary_ids = array_values(array_unique([
            ...array_map('intval', $this->beneficiary_ids),
            ...array_map('intval', $ids),
        ]));

        return count($this->beneficiary_ids) - $before;
    }

    /**
     * Ticking a category chip merges every eligible beneficiary in the chosen
     * categories into {@see $beneficiary_ids}. Capped at {@see MAX_SELECT_ALL}
     * ids per change for safety, with a toast when the match count exceeds it.
     */
    public function updatedCategoryIds(): void
    {
        $this->category_ids = array_values(array_unique(
            array_map('intval', $this->category_ids),
        ));

        if ($this->category_ids === []) {
            return;
        }

        $query = $this->eligibleBeneficiariesQuery()
            ->whereHas('categories', fn (Builder $q): Builder => $q
                ->whereIn('beneficiary_categories.id', $this->category_ids));

        $total = (clone $query)->count();

        $ids = (clone $query)
            ->orderBy('first_name')
            ->limit(self::MAX_SELECT_ALL)
            ->pluck('id')
            ->all();

        $this->mergeBeneficiaryIds($ids);

        if ($total > self::MAX_SELECT_ALL) {
            $this->dispatch('toast', type: 'info', message: __('aids.select_all_capped', ['count' => self::MAX_SELECT_ALL]));
        }
    }

    /**
     * Read the uploaded workbook, pull out every Saudi national id it contains,
     * match them to eligible beneficiaries, merge the matches into the
     * selection and report the national ids that matched nothing. Ported from
     * {@see BatchCreate::updatedNationalIdFile()}.
     */
    public function updatedNationalIdFile(): void
    {
        Gate::authorize('create', Aid::class);

        $this->validate([
            'nationalIdFile' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $path = $this->nationalIdFile->store('imports', self::DISK);

        try {
            $rows = SpreadsheetReader::rows($path, self::DISK);
        } finally {
            if (Storage::disk(self::DISK)->exists($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
        }

        // Collect every cell that looks like a Saudi national id, so header
        // labels and stray text never enter the matching set.
        $candidates = collect($rows)
            ->flatten()
            ->map(fn ($cell): string => trim((string) $cell))
            ->filter(fn (string $cell): bool => preg_match('/^[12]\d{9}$/', $cell) === 1)
            ->unique()
            ->values();

        if ($candidates->isEmpty()) {
            $this->unmatchedNationalIds = [];
            $this->reset('nationalIdFile');
            $this->addError('nationalIdFile', __('aid_batches.excel_no_ids'));

            return;
        }

        $matched = $this->eligibleBeneficiariesQuery()
            ->whereIn('national_id', $candidates->all())
            ->get(['id', 'national_id']);

        $this->mergeBeneficiaryIds($matched->pluck('id')->all());

        $this->unmatchedNationalIds = $candidates
            ->diff($matched->pluck('national_id'))
            ->values()
            ->all();

        $this->reset('nationalIdFile');

        $this->dispatch('toast', type: 'success', message: __('aid_batches.excel_matched', [
            'matched' => $matched->count(),
            'unmatched' => count($this->unmatchedNationalIds),
        ]));
    }

    #[Computed]
    public function isInKind(): bool
    {
        return $this->type === AidType::InKind->value;
    }

    public function addItem(): void
    {
        $this->items[] = [
            'name' => '',
            'quantity' => 1,
            'estimated_value' => null,
            'description' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);

        $this->items = array_values($this->items);
    }

    /**
     * Force the aid type to match a program that only supports one type
     * (Cash/InKind); a "both" program leaves the current choice untouched.
     */
    public function updatedAidProgramId(): void
    {
        $program = AidProgram::find($this->aid_program_id);

        if (! $program) {
            return;
        }

        $forcedType = match ($program->type) {
            AidProgramType::Cash => AidType::Cash,
            AidProgramType::InKind => AidType::InKind,
            AidProgramType::Both => null,
        };

        if ($forcedType !== null && $forcedType->value !== $this->type) {
            $this->type = $forcedType->value;
            $this->resetTypeSpecificFields();
        }
    }

    public function updatedType(): void
    {
        $this->resetTypeSpecificFields();
    }

    /**
     * Cash and in-kind fields are mutually exclusive: switching type clears
     * whichever set no longer applies.
     */
    private function resetTypeSpecificFields(): void
    {
        if ($this->type === AidType::Cash->value) {
            $this->items = [];

            return;
        }

        $this->amount = null;
        $this->purpose = '';
        $this->overrideAmounts = [];
    }

    public function save(): void
    {
        $result = $this->persist();

        if ($result === null) {
            return;
        }

        if ($result instanceof Aid) {
            $this->redirectRoute('aids.show', $result, navigate: true);

            return;
        }

        $this->redirectRoute('aids.index', navigate: true);
    }

    public function saveAndSubmit(): void
    {
        $this->showSubmitConfirm = false;

        $result = $this->persist();

        if ($result === null) {
            return;
        }

        if ($result instanceof Aid) {
            try {
                app(SubmitAid::class)->handle($result, Auth::user());

                $this->dispatch('toast', type: 'success', message: __('aids.messages.submitted'));
            } catch (InvalidAidTransitionException|AuthorizationException $exception) {
                $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            }

            $this->redirectRoute('aids.show', $result, navigate: true);

            return;
        }

        // Bulk create: submit every aid independently, one exception at a
        // time, so one beneficiary's failure doesn't block the rest.
        $submittedCount = 0;

        foreach ($result as $aid) {
            try {
                app(SubmitAid::class)->handle($aid, Auth::user());

                $submittedCount++;
            } catch (InvalidAidTransitionException|AuthorizationException $exception) {
                $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            }
        }

        if ($submittedCount > 0) {
            $this->dispatch('toast', type: 'success', message: __('aids.messages.bulk_submitted', ['count' => $submittedCount]));
        }

        $this->redirectRoute('aids.index', navigate: true);
    }

    /**
     * Validate, save the aid(s), and dispatch a success toast.
     *
     * In edit mode (a single, existing aid) this returns the updated Aid,
     * unchanged from before multi-beneficiary create was added. In create
     * mode it raises one independent aid per selected beneficiary — all
     * sharing the same program/type/amount-or-items/notes — and returns
     * them as a Collection (even when only one beneficiary was picked, so
     * {@see save()}/{@see saveAndSubmit()} tell single- and multi-create
     * apart by checking whether more than one aid came back).
     *
     * Returns null (without persisting anything) if validation or the
     * type/program compatibility check fails, so both callers can bail out
     * early.
     *
     * @return Aid|Collection<int, Aid>|null
     */
    private function persist(): Aid|Collection|null
    {
        $isUpdate = $this->aid?->exists ?? false;

        Gate::authorize($isUpdate ? 'update' : 'create', $this->aid ?? Aid::class);

        $validated = $this->validate($this->validationRules($isUpdate));

        $program = AidProgram::findOrFail($validated['aid_program_id']);

        try {
            app(AssertAidTypeMatchesProgram::class)->handle(AidType::from($validated['type']), $program);
        } catch (InvalidArgumentException $exception) {
            $this->addError('aid_program_id', $exception->getMessage());

            return null;
        }

        if ($isUpdate) {
            try {
                $aid = app(UpdateAid::class)->handle($this->aid, $validated);
            } catch (InvalidAidTransitionException $exception) {
                $this->dispatch('toast', type: 'error', message: $exception->getMessage());

                return null;
            }

            // UpdateAid doesn't own the optional title, so persist it here.
            $aid->update(['title' => $validated['title'] ?? null]);

            $this->aid = $aid;

            $this->syncRecurringPlan($aid);
            $this->storeDocuments($aid);

            $this->dispatch('toast', type: 'success', message: __('aids.messages.saved'));

            return $aid;
        }

        $beneficiaryIds = $validated['beneficiary_ids'];
        unset($validated['beneficiary_ids']);

        // Write-path eligibility re-check (preserves the guard the batch flow
        // enforced before it was folded into this screen): beneficiary_ids is
        // a client-controllable property, so drop any id that isn't an eligible
        // (non-suspended/deactivated/rejected) beneficiary before raising aids,
        // so a crafted request can't aid an ineligible beneficiary.
        $beneficiaryIds = $this->eligibleBeneficiariesQuery()
            ->whereIn('id', $beneficiaryIds)
            ->pluck('id')
            ->all();

        if ($beneficiaryIds === []) {
            $this->addError('beneficiary_ids', __('aid_batches.no_eligible_selected'));

            return null;
        }

        $isCash = ($validated['type'] ?? null) === AidType::Cash->value;

        $createdAids = collect($beneficiaryIds)
            ->map(function (int $beneficiaryId) use ($validated, $isCash): Aid {
                $data = [...$validated, 'beneficiary_id' => $beneficiaryId];

                // Per-beneficiary cash override wins over the form amount; a
                // blank/absent override falls back to $validated['amount'].
                if ($isCash) {
                    $override = $this->overrideAmounts[$beneficiaryId] ?? null;

                    if ($override !== null && $override !== '') {
                        $data['amount'] = (float) $override;
                    }
                }

                $aid = app(CreateAid::class)->handle($data, Auth::user());

                $this->syncRecurringPlan($aid);
                $this->storeDocuments($aid);

                return $aid;
            });

        if ($createdAids->count() === 1) {
            $aid = $createdAids->first();

            $this->aid = $aid;

            $this->dispatch('toast', type: 'success', message: __('aids.messages.saved'));

            return $aid;
        }

        $this->dispatch('toast', type: 'success', message: __('aids.messages.bulk_created', ['count' => $createdAids->count()]));

        return $createdAids;
    }

    /**
     * Create, update or remove the aid's recurrence plan to match the form.
     * A single plan row is kept per aid ({@see Aid::recurringPlan()}): it is
     * upserted while {@see $isRecurring} is on and deleted once it's turned
     * off.
     */
    private function syncRecurringPlan(Aid $aid): void
    {
        if (! $this->isRecurring) {
            $aid->recurringPlan()->delete();

            return;
        }

        $frequency = RecurrenceFrequency::from($this->recurrenceFrequency);
        $intervalMonths = $frequency->isCustom() ? $this->recurrenceIntervalMonths : null;
        $startsOn = CarbonImmutable::parse($this->recurrenceStartsOn)->startOfDay();
        $dueOn = $this->recurrenceDueOn !== null && $this->recurrenceDueOn !== ''
            ? CarbonImmutable::parse($this->recurrenceDueOn)->startOfDay()
            : null;
        $endsOn = $this->recurrenceEndsOn !== null && $this->recurrenceEndsOn !== ''
            ? CarbonImmutable::parse($this->recurrenceEndsOn)->startOfDay()
            : null;
        $titleTemplate = $this->recurrenceTitleTemplate !== null && trim($this->recurrenceTitleTemplate) !== ''
            ? $this->recurrenceTitleTemplate
            : null;

        // The first cycle fires on the due date when one is given, otherwise
        // on the schedule start date.
        $anchor = $dueOn ?? $startsOn;

        $existing = $aid->recurringPlan;

        // Preserve an already-advanced next_run_on on edit unless the anchor
        // (due date, or start date when no due date) itself moved; a brand-new
        // plan simply fires first on its anchor date.
        $existingAnchor = $existing?->due_on ?? $existing?->starts_on;

        $nextRunOn = $existing !== null && $existingAnchor?->toDateString() === $anchor->toDateString()
            ? $existing->next_run_on
            : $anchor;

        $aid->recurringPlan()->updateOrCreate([], [
            'frequency' => $frequency,
            'interval_months' => $intervalMonths,
            'starts_on' => $startsOn,
            'due_on' => $dueOn,
            'title_template' => $titleTemplate,
            'ends_on' => $endsOn,
            'next_run_on' => $nextRunOn,
            'lead_days' => max(0, $this->recurrenceLeadDays),
            'is_active' => $this->recurrenceActive,
        ]);
    }

    /**
     * Move every pending upload into the aid's private 'aid_documents'
     * media collection. preservingOriginal keeps the temporary file intact
     * so the same set of documents can be attached to each aid of a bulk
     * create.
     */
    private function storeDocuments(Aid $aid): void
    {
        foreach ($this->documents as $document) {
            if (! $document instanceof TemporaryUploadedFile) {
                continue;
            }

            $aid->addMedia($document->getRealPath())
                ->preservingOriginal()
                ->usingName($document->getClientOriginalName())
                ->usingFileName($document->getClientOriginalName())
                ->toMediaCollection('aid_documents');
        }
    }

    /**
     * Validation rules shared by {@see persist()} and the submit-confirm
     * pre-flight ({@see confirmSubmit()}).
     *
     * @return array<string, mixed>
     */
    private function validationRules(bool $isUpdate): array
    {
        $isInKind = $this->type === AidType::InKind->value;

        $rules = [
            'aid_program_id' => [
                'required',
                'integer',
                Rule::exists('aid_programs', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AidType::class)],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'required_if:type,cash'],
            'purpose' => ['nullable', 'string', 'max:255', 'required_if:type,cash'],
            'notes' => ['nullable', 'string'],
            'items' => $isInKind ? ['required', 'array', 'min:1'] : ['array'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            // Phase 9: optional supporting documents (PDF/JPG/PNG, 5 MB each).
            'documents' => ['array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

        if ($this->isRecurring) {
            $rules['recurrenceFrequency'] = ['required', Rule::enum(RecurrenceFrequency::class)];
            $rules['recurrenceIntervalMonths'] = [
                'nullable', 'integer', 'min:1', 'max:60',
                Rule::requiredIf($this->recurrenceFrequency === RecurrenceFrequency::CustomMonths->value),
            ];
            $rules['recurrenceStartsOn'] = ['required', 'date'];
            $rules['recurrenceDueOn'] = ['nullable', 'date'];
            $rules['recurrenceTitleTemplate'] = ['nullable', 'string', 'max:255'];
            $rules['recurrenceEndsOn'] = ['nullable', 'date', 'after_or_equal:recurrenceStartsOn'];
            $rules['recurrenceLeadDays'] = ['required', 'integer', 'min:0', 'max:365'];
        }

        if ($isUpdate) {
            $rules['beneficiary_id'] = [
                'required',
                'integer',
                Rule::exists('beneficiaries', 'id')->whereNull('deleted_at'),
            ];
        } else {
            $rules['beneficiary_ids'] = ['required', 'array', 'min:1'];
            $rules['beneficiary_ids.*'] = [
                'integer',
                Rule::exists('beneficiaries', 'id')->whereNull('deleted_at'),
            ];

            // Per-beneficiary cash override (create mode only).
            if (! $isInKind) {
                $rules['overrideAmounts.*'] = ['nullable', 'numeric', 'min:0.01'];
            }
        }

        return $rules;
    }

    /**
     * Validate the form and, if valid, open the "confirm submit" modal so
     * the actor reviews what will be created/submitted. An invalid form
     * surfaces its errors inline and never opens the modal.
     */
    public function confirmSubmit(): void
    {
        if (! $this->passesSubmitPreflight()) {
            return;
        }

        $this->showSubmitConfirm = true;
    }

    public function cancelSubmit(): void
    {
        $this->showSubmitConfirm = false;
    }

    private function passesSubmitPreflight(): bool
    {
        $isUpdate = $this->aid?->exists ?? false;

        Gate::authorize($isUpdate ? 'update' : 'create', $this->aid ?? Aid::class);

        $validated = $this->validate($this->validationRules($isUpdate));

        $program = AidProgram::findOrFail($validated['aid_program_id']);

        try {
            app(AssertAidTypeMatchesProgram::class)->handle(AidType::from($validated['type']), $program);
        } catch (InvalidArgumentException $exception) {
            $this->addError('aid_program_id', $exception->getMessage());

            return false;
        }

        return true;
    }

    public function render()
    {
        return view('livewire.aids.form');
    }
}
