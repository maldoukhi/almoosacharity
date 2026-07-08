<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\AssertAidTypeMatchesProgram;
use App\Actions\Aids\CreateAid;
use App\Actions\Aids\SubmitAid;
use App\Actions\Aids\UpdateAid;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Aid create/edit form. Editing an existing aid is only reachable while it
 * is still a draft: AidPolicy::update() denies the ability entirely once
 * the aid has left that status, so Gate::authorize() below already turns
 * into a 403 in that case.
 */
class Form extends Component
{
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

    public ?int $aid_program_id = null;

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
        $matches = Beneficiary::query()
            ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'national_id'])
            ->when($this->beneficiarySearch !== '', function (Builder $query): void {
                $term = "%{$this->beneficiarySearch}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('second_name', 'like', $term)
                        ->orWhere('third_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term);
                });
            })
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
    }

    public function save(): void
    {
        $result = $this->persist();

        if ($result === null) {
            return;
        }

        if ($result instanceof Aid) {
            $this->redirectRoute('aids.show', ['aid' => $result->id], navigate: true);

            return;
        }

        $this->redirectRoute('aids.index', navigate: true);
    }

    public function saveAndSubmit(): void
    {
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

            $this->redirectRoute('aids.show', ['aid' => $result->id], navigate: true);

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

        $isInKind = $this->type === AidType::InKind->value;

        $rules = [
            'aid_program_id' => [
                'required',
                'integer',
                Rule::exists('aid_programs', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(AidType::class)],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'required_if:type,cash'],
            'purpose' => ['nullable', 'string', 'max:255', 'required_if:type,cash'],
            'notes' => ['nullable', 'string'],
            'items' => $isInKind ? ['required', 'array', 'min:1'] : ['array'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ];

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
        }

        $validated = $this->validate($rules);

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

            $this->aid = $aid;

            $this->dispatch('toast', type: 'success', message: __('aids.messages.saved'));

            return $aid;
        }

        $beneficiaryIds = $validated['beneficiary_ids'];
        unset($validated['beneficiary_ids']);

        $createdAids = collect($beneficiaryIds)
            ->map(fn (int $beneficiaryId): Aid => app(CreateAid::class)->handle(
                [...$validated, 'beneficiary_id' => $beneficiaryId],
                Auth::user(),
            ));

        if ($createdAids->count() === 1) {
            $aid = $createdAids->first();

            $this->aid = $aid;

            $this->dispatch('toast', type: 'success', message: __('aids.messages.saved'));

            return $aid;
        }

        $this->dispatch('toast', type: 'success', message: __('aids.messages.bulk_created', ['count' => $createdAids->count()]));

        return $createdAids;
    }

    public function render()
    {
        return view('livewire.aids.form');
    }
}
