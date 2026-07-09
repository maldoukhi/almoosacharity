<?php

namespace App\Livewire\Beneficiaries;

use App\Actions\BeneficiaryFlows\RecordBeneficiaryDecision;
use App\Actions\BeneficiaryFlows\ResolveNextBeneficiaryStage;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Models\Beneficiary;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;

/**
 * Confirmation modal for a beneficiary review decision (approve / reject /
 * return) at the beneficiary's current stage — mirrors the aid
 * ApprovalDecisionModal: it carries the note field (required for
 * reject/return) and previews the consequence of the decision.
 */
class BeneficiaryDecisionModal extends ModalComponent
{
    public Beneficiary $beneficiary;

    public string $action = '';

    public string $note = '';

    public function mount(Beneficiary $beneficiary, string $action): void
    {
        $this->beneficiary = $beneficiary;
        $this->action = $action;

        Gate::authorize('review', $this->beneficiary);

        if (! $this->reviewAction()) {
            abort(404);
        }
    }

    private function reviewAction(): ?ApprovalAction
    {
        return ApprovalAction::tryFrom($this->action);
    }

    #[Computed]
    public function requiresNote(): bool
    {
        return (bool) $this->reviewAction()?->requiresNote();
    }

    #[Computed]
    public function actionLabel(): string
    {
        return $this->reviewAction()?->label() ?? '';
    }

    /**
     * The next stage after the current one, if this is an approve that is not
     * the final stage — used to preview who gets notified.
     */
    #[Computed]
    public function nextStage(): mixed
    {
        if ($this->reviewAction() !== ApprovalAction::Approve) {
            return null;
        }

        return app(ResolveNextBeneficiaryStage::class)->handle($this->beneficiary);
    }

    /**
     * True when an approve here is the final one (moves the beneficiary to
     * Active rather than a next-stage reviewer).
     */
    #[Computed]
    public function isFinalApprove(): bool
    {
        return $this->reviewAction() === ApprovalAction::Approve && $this->nextStage() === null;
    }

    #[Computed]
    public function nextReviewerRoleLabel(): ?string
    {
        $stage = $this->nextStage();

        if (! $stage) {
            return null;
        }

        return RoleName::tryFrom((string) $stage->role)?->label() ?? $stage->role;
    }

    #[Computed]
    public function nextRecipientCount(): int
    {
        $stage = $this->nextStage();

        if (! $stage) {
            return 0;
        }

        return $stage->eligibleUsers()
            ->where('status', UserStatus::Active)
            ->count();
    }

    public function confirm(): void
    {
        Gate::authorize('review', $this->beneficiary);

        $action = $this->reviewAction();

        if (! $action) {
            abort(404);
        }

        if ($action->requiresNote()) {
            $this->validate([
                'note' => ['required', 'string', 'max:2000'],
            ], [
                'note.required' => __('beneficiaries.flow.errors.note_required'),
            ]);
        }

        try {
            app(RecordBeneficiaryDecision::class)->handle(
                $this->beneficiary,
                Auth::user(),
                $action,
                $this->note !== '' ? $this->note : null,
            );
        } catch (InvalidBeneficiaryTransitionException|InvalidArgumentException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            $this->closeModal();

            return;
        }

        $this->dispatch('toast', type: 'success', message: __(match ($action) {
            ApprovalAction::Approve => 'beneficiaries.flow.messages.approved',
            ApprovalAction::Reject => 'beneficiaries.flow.messages.rejected',
            ApprovalAction::Return => 'beneficiaries.flow.messages.returned',
        }));

        $this->dispatch('beneficiary-reviewed');

        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        return view('livewire.beneficiaries.beneficiary-decision-modal');
    }
}
