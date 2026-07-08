<?php

namespace App\Livewire\Aids;

use App\Actions\Approvals\RecordApprovalDecision;
use App\Actions\Approvals\ResolveNextStage;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;

/**
 * Confirmation modal for an approval decision (approve / reject / return).
 * Replaces the browser `confirm()` and the inline note textarea with a
 * modal that carries the note field (required for reject/return) and a
 * preview of who the decision notifies — the next stage's reviewers on a
 * non-final approve, the beneficiary on a final approve, and no one on
 * reject/return — so the actor sees the consequence before committing.
 */
class ApprovalDecisionModal extends ModalComponent
{
    public Aid $aid;

    public string $action = '';

    public string $note = '';

    public function mount(Aid $aid, string $action): void
    {
        $this->aid = $aid;
        $this->action = $action;

        Gate::authorize('act', $this->aid);

        if (! $this->approvalAction()) {
            abort(404);
        }
    }

    private function approvalAction(): ?ApprovalAction
    {
        return ApprovalAction::tryFrom($this->action);
    }

    #[Computed]
    public function requiresNote(): bool
    {
        return (bool) $this->approvalAction()?->requiresNote();
    }

    #[Computed]
    public function actionLabel(): string
    {
        return $this->approvalAction()?->label() ?? '';
    }

    /**
     * The next stage after the current one, if this is an approve that is
     * not the final stage — used to preview who gets notified.
     */
    #[Computed]
    public function nextStage(): mixed
    {
        if ($this->approvalAction() !== ApprovalAction::Approve) {
            return null;
        }

        return app(ResolveNextStage::class)->handle($this->aid);
    }

    /**
     * True when an approve here is the final one (moves the aid to Approved
     * and notifies the beneficiary rather than a next-stage reviewer).
     */
    #[Computed]
    public function isFinalApprove(): bool
    {
        return $this->approvalAction() === ApprovalAction::Approve && $this->nextStage() === null;
    }

    #[Computed]
    public function nextReviewerRoleLabel(): ?string
    {
        $stage = $this->nextStage();

        if (! $stage) {
            return null;
        }

        return RoleName::tryFrom($stage->role)?->label() ?? $stage->role;
    }

    #[Computed]
    public function nextRecipientCount(): int
    {
        $stage = $this->nextStage();

        if (! $stage) {
            return 0;
        }

        return User::role($stage->role)
            ->where('status', UserStatus::Active)
            ->count();
    }

    public function confirm(): void
    {
        Gate::authorize('act', $this->aid);

        $action = $this->approvalAction();

        if (! $action) {
            abort(404);
        }

        if ($action->requiresNote()) {
            $this->validate([
                'note' => ['required', 'string', 'max:2000'],
            ], [
                'note.required' => __('validation.custom.approval.note_required'),
            ]);
        }

        try {
            app(RecordApprovalDecision::class)->handle(
                $this->aid,
                Auth::user(),
                $action,
                $this->note !== '' ? $this->note : null,
            );
        } catch (InvalidAidTransitionException|InvalidArgumentException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            $this->closeModal();

            return;
        }

        $this->dispatch('toast', type: 'success', message: __(match ($action) {
            ApprovalAction::Approve => 'approvals.messages.approved',
            ApprovalAction::Reject => 'approvals.messages.rejected',
            ApprovalAction::Return => 'approvals.messages.returned',
        }));

        $this->dispatch('aid-submitted');

        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        return view('livewire.aids.approval-decision-modal');
    }
}
