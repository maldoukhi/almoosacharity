<?php

namespace App\Livewire\Settings;

use App\Policies\AidPolicy;
use App\Support\FiscalLock as FiscalLockSupport;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Fiscal-year lock settings screen: closes an accounting year so aids that
 * belong to it (by creation year) can no longer be edited, cancelled or
 * deleted — only viewed and exported. Backed by the single
 * `fiscal_locked_until_year` setting; enforcement lives in
 * {@see FiscalLockSupport} / {@see AidPolicy}.
 */
class FiscalLock extends Component
{
    /** The year through which the books are closed, or null for no lock. */
    public ?int $year = null;

    public function mount(): void
    {
        Gate::authorize('notifications.settings.manage');

        $this->year = FiscalLockSupport::lockedUntilYear();
    }

    /**
     * The currently persisted lock year (null when unset) — read straight
     * from the shared support helper so the "current lock" banner always
     * reflects what enforcement actually uses.
     */
    #[Computed]
    public function currentLock(): ?int
    {
        return FiscalLockSupport::lockedUntilYear();
    }

    public function save(): void
    {
        Gate::authorize('notifications.settings.manage');

        $this->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.now()->year],
        ]);

        app(Settings::class)->set(
            'fiscal_locked_until_year',
            $this->year !== null ? (string) $this->year : null,
        );

        unset($this->currentLock);

        $this->dispatch('toast', type: 'success', message: __('reports.fiscal.saved'));
    }

    public function clearLock(): void
    {
        Gate::authorize('notifications.settings.manage');

        app(Settings::class)->set('fiscal_locked_until_year', null);

        $this->year = null;
        unset($this->currentLock);

        $this->dispatch('toast', type: 'success', message: __('reports.fiscal.cleared'));
    }

    public function render()
    {
        return view('livewire.settings.fiscal-lock');
    }
}
