<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\CancelAid;
use App\Actions\Aids\SubmitAid;
use App\Actions\Approvals\RecordApprovalDecision;
use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Aid detail screen: timeline, decisions, items and the approval decision
 * form for whoever holds the current stage's role.
 */
class Show extends Component
{
    public Aid $aid;

    public string $decisionNote = '';

    public function mount(Aid $aid): void
    {
        $this->aid = $aid;

        Gate::authorize('view', $this->aid);

        $this->eagerLoad();
    }

    /**
     * The ordered stages of the flow that applies to this aid — its own
     * submitted-flow snapshot once it exists, otherwise the flow the
     * assigned program (or the default active flow) would use once
     * submitted, so the timeline can be previewed before submission —
     * shaped for x-ui.stepper (label/meta pairs).
     *
     * @return Collection<int, array{label: string, meta: ?string}>
     */
    #[Computed]
    public function stages(): Collection
    {
        return $this->resolveStages()->map(fn (ApprovalFlowStage $stage): array => [
            'label' => $stage->name,
            'meta' => RoleName::tryFrom($stage->role)?->label() ?? $stage->role,
        ]);
    }

    /**
     * Zero-based position of the aid's current stage within {@see stages},
     * or null if the aid has no current stage (not yet submitted, or the
     * workflow has already concluded).
     */
    #[Computed]
    public function currentStageIndex(): ?int
    {
        if (! $this->aid->current_stage_id) {
            return null;
        }

        $index = $this->resolveStages()->search(
            fn (ApprovalFlowStage $stage): bool => $stage->id === $this->aid->current_stage_id,
        );

        return $index === false ? null : $index;
    }

    /**
     * The decisions recorded against this aid, newest first, in the shape
     * x-ui.timeline reads directly off an ApprovalDecision instance.
     *
     * @return Collection<int, ApprovalDecision>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->aid->decisions;
    }

    /**
     * The raw, ordered stage models behind {@see stages()} and
     * {@see currentStageIndex()} — kept separate since the latter needs
     * each stage's id, which the label/meta shape above discards.
     *
     * @return Collection<int, ApprovalFlowStage>
     */
    private function resolveStages(): Collection
    {
        $flow = $this->aid->approvalFlow
            ?? $this->aid->program?->approvalFlow
            ?? ApprovalFlow::query()->default()->where('is_active', true)->first();

        return $flow?->stages ?? collect();
    }

    #[Computed]
    public function canAct(): bool
    {
        return Gate::allows('act', $this->aid);
    }

    /**
     * The approval actions allowed at the aid's current stage.
     *
     * @return array<int, ApprovalAction>
     */
    #[Computed]
    public function allowedActions(): array
    {
        $stage = $this->aid->currentStage;

        if (! $stage) {
            return [];
        }

        return collect(ApprovalAction::cases())
            ->filter(fn (ApprovalAction $action): bool => $stage->allows($action))
            ->values()
            ->all();
    }

    #[Computed]
    public function canSubmit(): bool
    {
        return Gate::allows('submit', $this->aid);
    }

    #[Computed]
    public function canCancel(): bool
    {
        return Gate::allows('cancel', $this->aid);
    }

    /**
     * The note from the most recent "return" decision, surfaced as a
     * heads-up banner while the aid is back in draft awaiting rework.
     */
    #[Computed]
    public function latestReturnNote(): ?string
    {
        if ($this->aid->status !== AidStatus::Draft) {
            return null;
        }

        return $this->aid->decisions
            ->firstWhere('action', ApprovalAction::Return)
            ?->note;
    }

    public function submit(): void
    {
        Gate::authorize('submit', $this->aid);

        try {
            app(SubmitAid::class)->handle($this->aid, Auth::user());

            $this->dispatch('toast', type: 'success', message: __('aids.messages.submitted'));
        } catch (InvalidAidTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->refreshAid();
    }

    public function decide(string $action): void
    {
        Gate::authorize('act', $this->aid);

        $approvalAction = ApprovalAction::tryFrom($action);

        if (! $approvalAction) {
            abort(404);
        }

        if ($approvalAction->requiresNote()) {
            $this->validate([
                'decisionNote' => ['required', 'string', 'max:2000'],
            ], [
                'decisionNote.required' => __('validation.custom.approval.note_required'),
            ]);
        }

        try {
            app(RecordApprovalDecision::class)->handle(
                $this->aid,
                Auth::user(),
                $approvalAction,
                $this->decisionNote !== '' ? $this->decisionNote : null,
            );
        } catch (InvalidAidTransitionException|InvalidArgumentException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->decisionNote = '';

        $this->refreshAid();

        $this->dispatch('toast', type: 'success', message: __(match ($approvalAction) {
            ApprovalAction::Approve => 'approvals.messages.approved',
            ApprovalAction::Reject => 'approvals.messages.rejected',
            ApprovalAction::Return => 'approvals.messages.returned',
        }));
    }

    public function cancel(): void
    {
        Gate::authorize('cancel', $this->aid);

        try {
            app(CancelAid::class)->handle($this->aid);
        } catch (InvalidAidTransitionException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('aids.messages.cancelled'));

        $this->refreshAid();
    }

    private function eagerLoad(): void
    {
        $this->aid->load([
            'beneficiary',
            'program.approvalFlow.stages',
            'items',
            'currentStage.approvalFlow',
            'decisions.user',
            'createdBy',
            'approvalFlow.stages',
        ]);
    }

    private function refreshAid(): void
    {
        $this->aid->refresh();

        $this->eagerLoad();

        unset(
            $this->stages,
            $this->currentStageIndex,
            $this->timeline,
            $this->canAct,
            $this->allowedActions,
            $this->canSubmit,
            $this->canCancel,
            $this->latestReturnNote,
        );
    }

    public function render()
    {
        return view('livewire.aids.show');
    }
}
