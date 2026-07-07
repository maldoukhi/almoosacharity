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

    public ?int $beneficiary_id = null;

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
     * or national ID, always including the currently selected beneficiary
     * (if any) so it never disappears from the picker once chosen.
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

        if ($this->beneficiary_id && ! $matches->contains('id', $this->beneficiary_id)) {
            $selected = Beneficiary::query()
                ->select(['id', 'first_name', 'second_name', 'third_name', 'last_name', 'national_id'])
                ->find($this->beneficiary_id);

            if ($selected) {
                $matches->prepend($selected);
            }
        }

        return $matches;
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
        $aid = $this->persist();

        if (! $aid) {
            return;
        }

        $this->redirectRoute('aids.show', ['aid' => $aid->id], navigate: true);
    }

    public function saveAndSubmit(): void
    {
        $aid = $this->persist();

        if (! $aid) {
            return;
        }

        try {
            app(SubmitAid::class)->handle($aid, Auth::user());

            $this->dispatch('toast', type: 'success', message: __('aids.messages.submitted'));
        } catch (InvalidAidTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
        }

        $this->redirectRoute('aids.show', ['aid' => $aid->id], navigate: true);
    }

    /**
     * Validate, save (create or update) the aid, and redirect to its show
     * page with a success toast. Returns null (without redirecting) if
     * validation or the type/program compatibility check fails, so
     * {@see saveAndSubmit()} can bail out early too.
     */
    private function persist(): ?Aid
    {
        $isUpdate = $this->aid?->exists ?? false;

        Gate::authorize($isUpdate ? 'update' : 'create', $this->aid ?? Aid::class);

        $isInKind = $this->type === AidType::InKind->value;

        $validated = $this->validate([
            'beneficiary_id' => [
                'required',
                'integer',
                Rule::exists('beneficiaries', 'id')->whereNull('deleted_at'),
            ],
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
        ]);

        $program = AidProgram::findOrFail($validated['aid_program_id']);

        try {
            app(AssertAidTypeMatchesProgram::class)->handle(AidType::from($validated['type']), $program);
        } catch (InvalidArgumentException $exception) {
            $this->addError('aid_program_id', $exception->getMessage());

            return null;
        }

        try {
            $aid = $isUpdate
                ? app(UpdateAid::class)->handle($this->aid, $validated)
                : app(CreateAid::class)->handle($validated, Auth::user());
        } catch (InvalidAidTransitionException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return null;
        }

        $this->aid = $aid;

        $this->dispatch('toast', type: 'success', message: __('aids.messages.saved'));

        return $aid;
    }

    public function render()
    {
        return view('livewire.aids.form');
    }
}
