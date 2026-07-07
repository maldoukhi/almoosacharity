<?php

namespace App\Livewire\Aids;

use App\Models\Aid;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the aid create/edit form. Field population, validation and
 * saving (via CreateAid/UpdateAid) are implemented in phase 3b; this
 * class only wires up the public property contract, authorization and
 * the target view.
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

    public function mount(?Aid $aid = null): void
    {
        $this->aid = $aid;

        Gate::authorize(
            $this->aid?->exists ? 'update' : 'create',
            $this->aid ?? Aid::class,
        );
    }

    public function render()
    {
        return view('livewire.aids.form');
    }
}
