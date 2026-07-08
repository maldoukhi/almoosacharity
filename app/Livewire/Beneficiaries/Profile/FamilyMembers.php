<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Enums\RelationKind;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * "Family members" tab of the beneficiary profile: inline CRUD list.
 */
class FamilyMembers extends Component
{
    public Beneficiary $beneficiary;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $name = '';

    public string $relation = '';

    public string $birth_date = '';

    public string $health_status = '';

    public string $education_status = '';

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * @return Collection<int, BeneficiaryFamilyMember>
     */
    #[Computed]
    public function members(): Collection
    {
        return $this->beneficiary->familyMembers()->orderBy('name')->get();
    }

    /**
     * @return array<int, RelationKind>
     */
    #[Computed]
    public function relations(): array
    {
        return RelationKind::cases();
    }

    public function addNew(): void
    {
        Gate::authorize('update', $this->beneficiary);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('update', $this->beneficiary);

        $member = $this->beneficiary->familyMembers()->findOrFail($id);

        $this->showForm = true;
        $this->editingId = $member->id;
        $this->name = $member->name;
        $this->relation = $member->relation->value;
        $this->birth_date = $member->birth_date?->format('Y-m-d') ?? '';
        $this->health_status = (string) $member->health_status;
        $this->education_status = (string) $member->education_status;
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

        if ($this->editingId) {
            $this->beneficiary->familyMembers()->whereKey($this->editingId)->update($validated);
        } else {
            $this->beneficiary->familyMembers()->create($validated);
        }

        unset($this->members);

        $this->resetForm();

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.family_member_saved'));
    }

    public function delete(int $id): void
    {
        Gate::authorize('update', $this->beneficiary);

        $this->beneficiary->familyMembers()->whereKey($id)->delete();

        unset($this->members);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.family_member_deleted'));
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'showForm', 'name', 'relation', 'birth_date', 'health_status', 'education_status']);
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.family-members');
    }
}
