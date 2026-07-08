<?php

namespace App\Livewire\Aids;

use App\Actions\Confirmations\ResendConfirmationLink;
use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Exceptions\Confirmations\AidConfirmationException;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Aid detail screen: timeline, decisions, items and the approval action
 * buttons. Submit, approve/reject/return and cancel each open their own
 * confirmation modal (SubmitAidModal / ApprovalDecisionModal /
 * CancelAidModal), which dispatch `aid-submitted` back here to refresh.
 */
class Show extends Component
{
    public Aid $aid;

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
        // The status guard matters for a system-admin, whose Gate::before
        // short-circuits the policy's own state checks: without it every
        // action button would show regardless of the aid's actual state.
        return $this->aid->status === AidStatus::UnderReview
            && $this->aid->current_stage_id !== null
            && Gate::allows('act', $this->aid);
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
        // Draft-only: without this a system-admin (Gate::before) would see
        // the "submit" button on an already-submitted aid.
        return $this->aid->status === AidStatus::Draft
            && Gate::allows('submit', $this->aid);
    }

    #[Computed]
    public function canCancel(): bool
    {
        return in_array($this->aid->status, [AidStatus::Draft, AidStatus::Submitted, AidStatus::UnderReview], true)
            && Gate::allows('cancel', $this->aid);
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

    /**
     * The aid's single delivery-confirmation-link record, once its
     * disbursement has been recorded as delivered (phase 6b) — null
     * before that point.
     */
    #[Computed]
    public function confirmation(): ?AidConfirmation
    {
        return $this->aid->confirmation;
    }

    #[Computed]
    public function canResendConfirmation(): bool
    {
        return $this->confirmation !== null && Gate::allows('resend', $this->confirmation);
    }

    public function resendConfirmation(): void
    {
        $confirmation = $this->confirmation;

        if (! $confirmation) {
            abort(404);
        }

        Gate::authorize('resend', $confirmation);

        try {
            app(ResendConfirmationLink::class)->handle($confirmation, Auth::user());
        } catch (AidConfirmationException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('confirmations.messages.resent'));

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
            'confirmation',
        ]);
    }

    /**
     * Also listens for 'disbursement-updated', dispatched by the nested
     * Disbursements\Panel component whenever it starts/records/confirms a
     * disbursement, and 'aid-submitted', dispatched by SubmitAidModal on a
     * successful submission — both change this aid's own status.
     */
    #[On('disbursement-updated')]
    #[On('aid-submitted')]
    public function refreshAid(): void
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
            $this->confirmation,
            $this->canResendConfirmation,
        );
    }

    public function render()
    {
        return view('livewire.aids.show');
    }
}
