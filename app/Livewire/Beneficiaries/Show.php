<?php

namespace App\Livewire\Beneficiaries;

use App\Models\Beneficiary;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Beneficiary profile screen: tabbed detail view delegating each tab's
 * content to a nested component under Beneficiaries\Profile.
 */
class Show extends Component
{
    /**
     * Tabs allowed for #[Url]-bound navigation; anything else is ignored.
     *
     * @var array<int, string>
     */
    private const TABS = ['basic', 'family', 'housing-income', 'bank', 'documents', 'activity'];

    public Beneficiary $beneficiary;

    #[Url]
    public string $activeTab = 'basic';

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary->load(['categories', 'familyMembers', 'incomeSources', 'creator']);

        Gate::authorize('view', $this->beneficiary);

        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'basic';
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.beneficiaries.show');
    }
}
