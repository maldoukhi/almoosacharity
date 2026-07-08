<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyMember;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;

/**
 * Read-only details of a single family member, opened by clicking a node
 * in the family-tree visual. Offers an "edit" shortcut that hands off to
 * FamilyMemberModal for users who may update the beneficiary.
 */
class FamilyMemberDetailModal extends ModalComponent
{
    public Beneficiary $beneficiary;

    public int $memberId;

    public function mount(Beneficiary $beneficiary, int $memberId): void
    {
        $this->beneficiary = $beneficiary;
        $this->memberId = $memberId;

        Gate::authorize('view', $this->beneficiary);

        // Scope the lookup to this beneficiary so a foreign member id 404s
        // rather than leaking another beneficiary's family data.
        abort_unless(
            $this->beneficiary->familyMembers()->whereKey($this->memberId)->exists(),
            404,
        );
    }

    #[Computed]
    public function member(): BeneficiaryFamilyMember
    {
        return $this->beneficiary->familyMembers()->findOrFail($this->memberId);
    }

    public static function modalMaxWidth(): string
    {
        return 'md';
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.family-member-detail-modal');
    }
}
