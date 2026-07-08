<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Enums\RelationKind;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;

/**
 * Add/edit a single family member inside a wire-elements modal (replaces
 * the old inline form in the family tab so its table stays uncluttered).
 * Dispatches `family-member-saved` on success so both the list
 * (FamilyMembers) and the family-tree visual (Show) can refresh.
 */
class FamilyMemberModal extends ModalComponent
{
    public Beneficiary $beneficiary;

    public ?int $memberId = null;

    public string $name = '';

    public string $relation = '';

    public string $birth_date = '';

    public string $health_status = '';

    public string $education_status = '';

    public function mount(Beneficiary $beneficiary, ?int $memberId = null): void
    {
        $this->beneficiary = $beneficiary;
        $this->memberId = $memberId;

        Gate::authorize('update', $this->beneficiary);

        if (! $this->memberId) {
            return;
        }

        $member = $this->beneficiary->familyMembers()->findOrFail($this->memberId);

        $this->name = $member->name;
        $this->relation = $member->relation->value;
        $this->birth_date = $member->birth_date?->format('Y-m-d') ?? '';
        $this->health_status = (string) $member->health_status;
        $this->education_status = (string) $member->education_status;
    }

    /**
     * @return array<int, RelationKind>
     */
    #[Computed]
    public function relations(): array
    {
        return RelationKind::cases();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->beneficiary);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'relation' => ['required', Rule::enum(RelationKind::class)],
            'birth_date' => ['nullable', 'date'],
            'health_status' => ['nullable', 'string'],
            'education_status' => ['nullable', 'string', 'max:255'],
        ]);

        // A blank date must be stored as null, not '' (MySQL strict mode
        // rejects '' for a date column; SQLite silently accepts it).
        if (($validated['birth_date'] ?? '') === '') {
            $validated['birth_date'] = null;
        }

        if ($this->memberId) {
            $this->beneficiary->familyMembers()->whereKey($this->memberId)->update($validated);
        } else {
            $this->beneficiary->familyMembers()->create($validated);
        }

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.family_member_saved'));
        $this->dispatch('family-member-saved');

        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'xl';
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.family-member-modal');
    }
}
