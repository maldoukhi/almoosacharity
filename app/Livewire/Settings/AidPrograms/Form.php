<?php

namespace App\Livewire\Settings\AidPrograms;

use App\Models\AidProgram;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the aid program create/edit form. Field population,
 * validation and saving (via SaveAidProgram) are implemented in phase
 * 3b; this class only wires up the public property contract,
 * authorization and the target view.
 */
class Form extends Component
{
    public ?AidProgram $program = null;

    public string $name = '';

    public string $type = 'both';

    public ?int $approval_flow_id = null;

    public bool $is_active = true;

    public string $description = '';

    public int $sort_order = 0;

    public function mount(?AidProgram $program = null): void
    {
        $this->program = $program;

        Gate::authorize('manage', $this->program ?? AidProgram::class);
    }

    public function render()
    {
        return view('livewire.settings.aid-programs.form');
    }
}
