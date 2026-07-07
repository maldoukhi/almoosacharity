<?php

namespace App\Livewire\Settings\AidPrograms;

use App\Actions\Settings\SaveAidProgram;
use App\Enums\AidProgramType;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Aid program create/edit form.
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

        if (! $this->program?->exists) {
            return;
        }

        $this->name = $this->program->name;
        $this->type = $this->program->type->value;
        $this->approval_flow_id = $this->program->approval_flow_id;
        $this->is_active = $this->program->is_active;
        $this->description = (string) $this->program->description;
        $this->sort_order = $this->program->sort_order;
    }

    /**
     * Active approval flows available to assign to this program.
     *
     * @return Collection<int, ApprovalFlow>
     */
    #[Computed]
    public function flows(): Collection
    {
        return ApprovalFlow::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, AidProgramType>
     */
    #[Computed]
    public function types(): Collection
    {
        return collect(AidProgramType::cases());
    }

    public function save(): void
    {
        Gate::authorize('manage', $this->program ?? AidProgram::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AidProgramType::class)],
            'approval_flow_id' => ['nullable', 'integer', 'exists:approval_flows,id'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        app(SaveAidProgram::class)->handle($validated, $this->program);

        $this->dispatch('toast', type: 'success', message: __('aids.programs.messages.saved'));

        $this->redirectRoute('admin.settings.aid-programs.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.aid-programs.form');
    }
}
