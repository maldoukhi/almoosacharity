<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Actions\Beneficiaries\RevealBankData;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * "Bank data" tab of the beneficiary profile: masked by default, with an
 * explicit, audited reveal action gated by beneficiaries.bank-data.view.
 */
class BankPanel extends Component
{
    public Beneficiary $beneficiary;

    public bool $revealed = false;

    public ?string $ibanReveal = null;

    public ?string $holderReveal = null;

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    #[Computed]
    public function canView(): bool
    {
        return Gate::allows('viewBankData', $this->beneficiary);
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('manageBankData', $this->beneficiary);
    }

    /**
     * A masked IBAN visible to anyone who can view the beneficiary, without
     * requiring the bank-data.view permission — falls back to the fully
     * masked/placeholder form for actors without that permission, and to
     * null when there is no IBAN on file at all.
     */
    #[Computed]
    public function maskedIban(): ?string
    {
        if (! $this->beneficiary->iban) {
            return null;
        }

        return $this->canView
            ? $this->beneficiary->maskedIban()
            : 'SA•• •••• •••• •• ••••';
    }

    public function reveal(): void
    {
        $data = app(RevealBankData::class)->handle($this->beneficiary, Auth::user());

        $this->ibanReveal = $data['iban'];
        $this->holderReveal = $data['holder'];
        $this->revealed = true;
    }

    public function hide(): void
    {
        $this->revealed = false;
        $this->ibanReveal = null;
        $this->holderReveal = null;
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.bank-panel');
    }
}
