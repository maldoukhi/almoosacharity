<?php

namespace App\Livewire\Settings\ApprovalFlows;

use App\Actions\Settings\SaveApprovalFlow;
use App\Enums\ApprovalAction;
use App\Enums\UserStatus;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Approval flow create/edit form (the flow itself plus its ordered
 * stages).
 */
class Form extends Component
{
    public ?ApprovalFlow $flow = null;

    public string $name = '';

    public bool $is_default = false;

    public bool $is_active = true;

    /** @var array<int, array{name: string, order: int, role: string, assignee_user_ids: array<int, int>, allowed_actions: array<int, string>}> */
    public array $stages = [];

    public function mount(?ApprovalFlow $flow = null): void
    {
        $this->flow = $flow;

        Gate::authorize('manage', $this->flow ?? ApprovalFlow::class);

        if ($this->flow?->exists) {
            $this->name = $this->flow->name;
            $this->is_default = $this->flow->is_default;
            $this->is_active = $this->flow->is_active;
            $this->stages = $this->flow->stages()
                ->orderBy('order')
                ->get()
                ->map(fn (ApprovalFlowStage $stage): array => [
                    'name' => $stage->name,
                    'order' => $stage->order,
                    'role' => (string) $stage->role,
                    'assignee_user_ids' => $stage->assigneeUserIds(),
                    'allowed_actions' => $stage->allowed_actions ?? [],
                ])
                ->all();

            return;
        }

        $this->stages = [$this->blankStage()];
    }

    /**
     * All Spatie role names, for the per-stage role select.
     *
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * Active users selectable as specific stage approvers.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<int, ApprovalAction>
     */
    #[Computed]
    public function availableActions(): array
    {
        return ApprovalAction::cases();
    }

    public function addStage(): void
    {
        $this->stages[] = $this->blankStage();

        $this->renumberStages();
    }

    /**
     * A flow must always keep at least one stage.
     */
    public function removeStage(int $index): void
    {
        if (count($this->stages) <= 1) {
            $this->dispatch('toast', type: 'error', message: __('approvals.flows.messages.at_least_one_stage'));

            return;
        }

        unset($this->stages[$index]);

        $this->stages = array_values($this->stages);

        $this->renumberStages();
    }

    public function moveStage(int $index, string $direction): void
    {
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($this->stages[$index]) || ! isset($this->stages[$target])) {
            return;
        }

        [$this->stages[$index], $this->stages[$target]] = [$this->stages[$target], $this->stages[$index]];

        $this->renumberStages();
    }

    public function save(): void
    {
        Gate::authorize('manage', $this->flow ?? ApprovalFlow::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'stages' => ['array', 'min:1'],
            'stages.*.name' => ['required', 'string', 'max:255'],
            'stages.*.role' => ['nullable', 'string', 'exists:roles,name'],
            'stages.*.assignee_user_ids' => ['array'],
            'stages.*.assignee_user_ids.*' => ['integer', 'exists:users,id'],
            'stages.*.allowed_actions' => ['array', 'min:1'],
            'stages.*.allowed_actions.*' => [Rule::enum(ApprovalAction::class)],
        ]);

        // Each stage must target a role, at least one user, or both.
        foreach ($this->stages as $index => $stage) {
            if (($stage['role'] ?? '') === '' && empty($stage['assignee_user_ids'])) {
                $this->addError("stages.{$index}.role", __('validation.custom.approval_flow.stage_assignee_required'));

                return;
            }
        }

        $data = [
            'name' => $this->name,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'stages' => collect($this->stages)
                ->map(fn (array $stage): array => [
                    'name' => $stage['name'],
                    'role' => $stage['role'] ?? '',
                    'assignee_user_ids' => array_map('intval', $stage['assignee_user_ids'] ?? []),
                    'allowed_actions' => array_values($stage['allowed_actions']),
                ])
                ->all(),
        ];

        try {
            app(SaveApprovalFlow::class)->handle($data, $this->flow);
        } catch (InvalidArgumentException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('approvals.flows.messages.saved'));

        $this->redirectRoute('admin.settings.approval-flows.index', navigate: true);
    }

    /**
     * @return array{name: string, order: int, role: string, assignee_user_ids: array<int, int>, allowed_actions: array<int, string>}
     */
    private function blankStage(): array
    {
        return [
            'name' => '',
            'order' => count($this->stages) + 1,
            'role' => '',
            'assignee_user_ids' => [],
            'allowed_actions' => [],
        ];
    }

    private function renumberStages(): void
    {
        $this->stages = collect($this->stages)
            ->values()
            ->map(function (array $stage, int $index): array {
                $stage['order'] = $index + 1;

                return $stage;
            })
            ->all();
    }

    public function render()
    {
        return view('livewire.settings.approval-flows.form');
    }
}
