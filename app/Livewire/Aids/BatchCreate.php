<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\AssertAidTypeMatchesProgram;
use App\Actions\Aids\CreateAidBatch;
use App\Enums\AidType;
use App\Enums\BeneficiaryStatus;
use App\Imports\SpreadsheetReader;
use App\Models\AidBatch;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Batch aid creation: pick one or more beneficiary categories (and/or upload
 * an Excel of national ids), review the matching active beneficiaries, then
 * raise a cash aid, an in-kind aid, or both for every selected beneficiary in
 * a single run — grouped under one {@see AidBatch}. Everything is
 * gated on aids.create.
 */
class BatchCreate extends Component
{
    use WithFileUploads;

    /**
     * Private disk the uploaded national-id workbook is parked on while we
     * read it: such lists carry national ids and must never touch the public
     * disk.
     */
    private const string DISK = 'local';

    /**
     * Safety cap on how many beneficiaries a single batch may target, whether
     * they were gathered by category or by uploaded national-id list.
     */
    private const int MAX_BENEFICIARIES = 500;

    /** @var array<int, int> */
    public array $category_ids = [];

    public ?int $aid_program_id = null;

    /** cash | in_kind | both */
    public string $mode = 'cash';

    public ?float $default_amount = null;

    public string $default_purpose = '';

    public string $note = '';

    /** @var array<int, array{name: string, quantity: int, estimated_value: ?float, description: string}> */
    public array $default_items = [];

    /** @var array<int, int> */
    public array $selected_ids = [];

    /**
     * Per-beneficiary cash amount override, keyed by beneficiary id. A blank
     * entry falls back to {@see $default_amount} at creation time.
     *
     * @var array<int, mixed>
     */
    public array $overrideAmounts = [];

    public mixed $nationalIdFile = null;

    /**
     * National ids from the last upload that matched no active beneficiary.
     *
     * @var array<int, string>
     */
    public array $unmatchedNationalIds = [];

    /**
     * Result of the last successful run, or null before one:
     * ['cash' => int, 'in_kind' => int, 'beneficiaries' => int].
     *
     * @var array<string, int>|null
     */
    public ?array $summary = null;

    public function mount(): void
    {
        Gate::authorize('aids.create');
    }

    /**
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
     * The active beneficiaries the batch can target: everyone in the chosen
     * categories, plus anyone already selected (e.g. matched from an uploaded
     * national-id list) so a selection never disappears from the table. Capped
     * at {@see MAX_BENEFICIARIES}.
     *
     * @return Collection<int, Beneficiary>
     */
    #[Computed]
    public function beneficiaries(): Collection
    {
        $byCategory = $this->category_ids !== []
            ? $this->activeBeneficiariesQuery()
                ->whereHas('categories', fn (Builder $query) => $query
                    ->whereIn('beneficiary_categories.id', $this->category_ids))
                ->orderBy('first_name')
                ->limit(self::MAX_BENEFICIARIES)
                ->get()
            : collect();

        $missingIds = array_diff($this->selected_ids, $byCategory->pluck('id')->all());

        if ($missingIds !== []) {
            $missing = $this->activeBeneficiariesQuery()
                ->whereIn('id', $missingIds)
                ->get();

            $byCategory = $missing->concat($byCategory);
        }

        return $byCategory
            ->unique('id')
            ->values();
    }

    private function activeBeneficiariesQuery(): Builder
    {
        // Eligible = anyone not suspended. Beneficiaries default to
        // "under_study" on registration, so restricting to Active only made
        // the batch list come back empty (they were all under study). A
        // suspended beneficiary is the only one that must never receive an
        // aid.
        return Beneficiary::query()
            ->whereNot('status', BeneficiaryStatus::Suspended->value)
            ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'national_id']);
    }

    #[Computed]
    public function isCash(): bool
    {
        return $this->mode === AidType::Cash->value || $this->mode === 'both';
    }

    #[Computed]
    public function isInKind(): bool
    {
        return $this->mode === AidType::InKind->value || $this->mode === 'both';
    }

    public function toggleBeneficiary(int $id): void
    {
        if (in_array($id, $this->selected_ids, true)) {
            $this->selected_ids = array_values(array_diff($this->selected_ids, [$id]));

            return;
        }

        if (count($this->selected_ids) >= self::MAX_BENEFICIARIES) {
            $this->dispatch('toast', type: 'info', message: __('aid_batches.selection_capped', ['count' => self::MAX_BENEFICIARIES]));

            return;
        }

        $this->selected_ids[] = $id;
    }

    /**
     * Select every currently-listed (category-matched) beneficiary, merging
     * with the existing selection and never exceeding the cap.
     */
    public function selectAll(): void
    {
        $ids = $this->beneficiaries->pluck('id')->all();

        $merged = array_values(array_unique([...$this->selected_ids, ...$ids]));

        if (count($merged) > self::MAX_BENEFICIARIES) {
            $merged = array_slice($merged, 0, self::MAX_BENEFICIARIES);
            $this->dispatch('toast', type: 'info', message: __('aid_batches.selection_capped', ['count' => self::MAX_BENEFICIARIES]));
        }

        $this->selected_ids = $merged;
    }

    public function clearSelection(): void
    {
        $this->selected_ids = [];
    }

    public function updatedMode(): void
    {
        if (! $this->isCash()) {
            $this->default_amount = null;
            $this->default_purpose = '';
            $this->overrideAmounts = [];
        }

        if (! $this->isInKind()) {
            $this->default_items = [];
        }
    }

    public function addItem(): void
    {
        $this->default_items[] = [
            'name' => '',
            'quantity' => 1,
            'estimated_value' => null,
            'description' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->default_items[$index]);

        $this->default_items = array_values($this->default_items);
    }

    /**
     * Read the uploaded workbook, pull out every Saudi national id it
     * contains, match them to active beneficiaries, add the matches to the
     * selection and report the national ids that matched nothing.
     */
    public function updatedNationalIdFile(): void
    {
        Gate::authorize('aids.create');

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

        $matched = $this->activeBeneficiariesQuery()
            ->whereIn('national_id', $candidates->all())
            ->get();

        $room = self::MAX_BENEFICIARIES - count($this->selected_ids);
        $newIds = array_values(array_diff($matched->pluck('id')->all(), $this->selected_ids));

        if ($room > 0) {
            $this->selected_ids = array_values(array_unique([
                ...$this->selected_ids,
                ...array_slice($newIds, 0, $room),
            ]));
        }

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

    public function create(): void
    {
        Gate::authorize('aids.create');

        $validated = $this->validate($this->rules());

        $program = AidProgram::findOrFail($validated['aid_program_id']);

        // The batch mode must be compatible with the program's type. Reuse
        // the same check a single aid create runs, once per type we'll raise.
        $typesToCheck = match ($this->mode) {
            'both' => [AidType::Cash, AidType::InKind],
            AidType::Cash->value => [AidType::Cash],
            default => [AidType::InKind],
        };

        foreach ($typesToCheck as $type) {
            try {
                app(AssertAidTypeMatchesProgram::class)->handle($type, $program);
            } catch (InvalidArgumentException $exception) {
                $this->addError('aid_program_id', $exception->getMessage());

                return;
            }
        }

        $beneficiaries = collect($this->selected_ids)
            ->map(function (int $id): array {
                $override = $this->overrideAmounts[$id] ?? null;

                return [
                    'id' => $id,
                    'amount' => ($override === null || $override === '') ? null : (float) $override,
                ];
            })
            ->all();

        $summary = app(CreateAidBatch::class)->handle([
            'aid_program_id' => $validated['aid_program_id'],
            'mode' => $this->mode,
            'note' => $this->note !== '' ? $this->note : null,
            'default_amount' => $this->default_amount,
            'default_purpose' => $this->default_purpose !== '' ? $this->default_purpose : null,
            'default_items' => $this->default_items,
            'beneficiaries' => $beneficiaries,
        ], Auth::user());

        $this->summary = [
            'cash' => $summary['cash'],
            'in_kind' => $summary['in_kind'],
            'beneficiaries' => $summary['beneficiaries'],
        ];

        $this->dispatch('toast', type: 'success', message: __('aid_batches.created', [
            'beneficiaries' => $summary['beneficiaries'],
        ]));

        $this->reset(['selected_ids', 'overrideAmounts', 'unmatchedNationalIds']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = [
            'aid_program_id' => [
                'required',
                'integer',
                Rule::exists('aid_programs', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'mode' => ['required', Rule::in([AidType::Cash->value, AidType::InKind->value, 'both'])],
            'note' => ['nullable', 'string', 'max:255'],
            'selected_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_BENEFICIARIES],
            'selected_ids.*' => [
                'integer',
                Rule::exists('beneficiaries', 'id')->whereNull('deleted_at'),
            ],
        ];

        if ($this->isCash()) {
            $rules['default_amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['default_purpose'] = ['required', 'string', 'max:255'];
            $rules['overrideAmounts.*'] = ['nullable', 'numeric', 'min:0.01'];
        }

        if ($this->isInKind()) {
            $rules['default_items'] = ['required', 'array', 'min:1'];
            $rules['default_items.*.name'] = ['required', 'string', 'max:255'];
            $rules['default_items.*.quantity'] = ['required', 'integer', 'min:1'];
            $rules['default_items.*.estimated_value'] = ['nullable', 'numeric', 'min:0'];
            $rules['default_items.*.description'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.aids.batch-create');
    }
}
