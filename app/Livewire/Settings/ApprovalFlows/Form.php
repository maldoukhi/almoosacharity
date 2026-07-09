<?php

namespace App\Livewire\Settings\ApprovalFlows;

use App\Actions\Settings\SaveApprovalFlow;
use App\Enums\ApprovalAction;
use App\Enums\ApprovalStageType;
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

    /** The notification channels a stage may fan out on (persisted only; the notifications domain does the actual sending). */
    public const NOTIFY_CHANNELS = ['in_app', 'email', 'whatsapp'];

    /** @var array<int, array{name: string, order: int, role: string, assignee_user_ids: array<int, int>, allowed_actions: array<int, string>, type: string, documents_required: bool, required_documents: array<int, array{label: string, required: bool}>, notify_channels: array<int, string>}> */
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
                    'type' => ($stage->type ?? ApprovalStageType::Approval)->value,
                    'documents_required' => (bool) $stage->documents_required,
                    // Normalize to {label, required} objects, tolerating legacy
                    // plain-string entries (treated as mandatory).
                    'required_documents' => $stage->requiredDocumentTypes(),
                    'notify_channels' => array_values($stage->notify_channels ?? []),
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

    /**
     * @return array<int, ApprovalStageType>
     */
    #[Computed]
    public function stageTypes(): array
    {
        return ApprovalStageType::cases();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function notifyChannels(): array
    {
        return self::NOTIFY_CHANNELS;
    }

    /**
     * Append a blank required-document row to a stage. New rows default to
     * mandatory; the builder toggle lets the admin mark them optional.
     */
    public function addDocumentType(int $index): void
    {
        if (! isset($this->stages[$index])) {
            return;
        }

        $this->stages[$index]['required_documents'][] = ['label' => '', 'required' => true];
    }

    /**
     * Remove a required-document label row from a stage.
     */
    public function removeDocumentType(int $index, int $documentIndex): void
    {
        if (! isset($this->stages[$index]['required_documents'][$documentIndex])) {
            return;
        }

        unset($this->stages[$index]['required_documents'][$documentIndex]);

        $this->stages[$index]['required_documents'] = array_values($this->stages[$index]['required_documents']);
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
            'stages.*.type' => ['nullable', Rule::enum(ApprovalStageType::class)],
            'stages.*.documents_required' => ['boolean'],
            'stages.*.required_documents' => ['array'],
            'stages.*.required_documents.*' => ['array'],
            'stages.*.required_documents.*.label' => ['nullable', 'string', 'max:255'],
            'stages.*.required_documents.*.required' => ['boolean'],
            'stages.*.notify_channels' => ['array'],
            'stages.*.notify_channels.*' => ['string', Rule::in(self::NOTIFY_CHANNELS)],
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
                    'type' => $stage['type'] ?? ApprovalStageType::Approval->value,
                    'documents_required' => (bool) ($stage['documents_required'] ?? false),
                    'required_documents' => collect($stage['required_documents'] ?? [])
                        ->map(fn ($document): array => is_array($document)
                            ? ['label' => (string) ($document['label'] ?? ''), 'required' => (bool) ($document['required'] ?? true)]
                            // Tolerate a legacy plain-string row that slipped through.
                            : ['label' => (string) $document, 'required' => true])
                        ->all(),
                    'notify_channels' => array_values(array_intersect(self::NOTIFY_CHANNELS, $stage['notify_channels'] ?? [])),
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
     * @return array{name: string, order: int, role: string, assignee_user_ids: array<int, int>, allowed_actions: array<int, string>, type: string, documents_required: bool, required_documents: array<int, array{label: string, required: bool}>, notify_channels: array<int, string>}
     */
    private function blankStage(): array
    {
        return [
            'name' => '',
            'order' => count($this->stages) + 1,
            'role' => '',
            'assignee_user_ids' => [],
            'allowed_actions' => [],
            'type' => ApprovalStageType::Approval->value,
            'documents_required' => false,
            'required_documents' => [],
            'notify_channels' => ['in_app'],
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
