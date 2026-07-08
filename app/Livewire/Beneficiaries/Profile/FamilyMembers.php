<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Family members" tab of the beneficiary profile: read-only list. Add
 * and edit are handled by FamilyMemberModal (wire-elements modal), which
 * dispatches `family-member-saved` back here (and to Show, for the
 * family-tree visual) on success.
 */
class FamilyMembers extends Component
{
    public Beneficiary $beneficiary;

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

    #[On('family-member-saved')]
    public function refreshList(): void
    {
        $this->beneficiary->unsetRelation('familyMembers');
        unset($this->members);
    }

    public function delete(int $id): void
    {
        Gate::authorize('update', $this->beneficiary);

        $this->beneficiary->familyMembers()->whereKey($id)->delete();

        $this->beneficiary->unsetRelation('familyMembers');
        unset($this->members);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.family_member_deleted'));
        $this->dispatch('family-member-saved');
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.family-members');
    }
}
