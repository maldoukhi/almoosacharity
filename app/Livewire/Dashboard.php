<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Placeholder metric for the first iteration of the dashboard; will be
     * expanded with beneficiaries/aids stats in later phases.
     */
    #[Computed]
    public function usersCount(): int
    {
        return User::query()->count();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
