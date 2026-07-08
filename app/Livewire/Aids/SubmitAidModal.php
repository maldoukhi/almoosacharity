<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\SubmitAid;
use App\Enums\AidType;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Listeners\NotifyStageApprovers;
use App\Models\Aid;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;

/**
 * Confirmation modal for submitting a draft aid into the approval workflow.
 * Replaces the browser `confirm()` with a summary of the aid plus a preview
 * of the notification that will fire (which stage it enters, which role
 * reviews it, how many active users get alerted, and on which channels) so
 * the actor sees exactly what submitting sets in motion before confirming.
 */
class SubmitAidModal extends ModalComponent
{
    public Aid $aid;

    public function mount(Aid $aid): void
    {
        $this->aid = $aid;

        Gate::authorize('submit', $this->aid);
    }

    /**
     * The first stage the aid enters on submission — resolved the same way
     * {@see SubmitAid} resolves the flow: the program's assigned flow if
     * active, otherwise the default active flow. Null if none is configured
     * (submission would then fail, and the modal says so).
     */
    #[Computed]
    public function firstStage(): ?ApprovalFlowStage
    {
        $flow = $this->aid->program?->approvalFlow;

        if (! $flow || ! $flow->is_active) {
            $flow = ApprovalFlow::query()->default()->where('is_active', true)->first();
        }

        return $flow?->stages()->orderBy('order')->first();
    }

    /**
     * Human-readable label of the role that reviews the first stage.
     */
    #[Computed]
    public function reviewerRoleLabel(): ?string
    {
        $stage = $this->firstStage();

        if (! $stage) {
            return null;
        }

        return RoleName::tryFrom($stage->role)?->label() ?? $stage->role;
    }

    /**
     * Count of active users holding the first stage's role — exactly who
     * {@see NotifyStageApprovers} will notify on submission.
     */
    #[Computed]
    public function recipientCount(): int
    {
        $stage = $this->firstStage();

        if (! $stage) {
            return 0;
        }

        return User::role($stage->role)
            ->where('status', UserStatus::Active)
            ->count();
    }

    /**
     * Short summary of what the aid grants, for the modal header.
     */
    #[Computed]
    public function amountSummary(): string
    {
        if ($this->aid->type === AidType::Cash) {
            return __('aids.currency_sar').' '.number_format((float) $this->aid->amount, 2);
        }

        return trans_choice('aids.items_count', $this->aid->items()->count(), [
            'count' => $this->aid->items()->count(),
        ]);
    }

    public function confirm(): void
    {
        Gate::authorize('submit', $this->aid);

        try {
            app(SubmitAid::class)->handle($this->aid, Auth::user());
        } catch (InvalidAidTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            $this->closeModal();

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('aids.messages.submitted'));
        $this->dispatch('aid-submitted');

        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        return view('livewire.aids.submit-aid-modal');
    }
}
