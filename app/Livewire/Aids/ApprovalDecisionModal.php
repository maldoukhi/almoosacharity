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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
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
    use WithFileUploads;

    public Aid $aid;

    public string $action = '';

    public string $note = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $documents = [];

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

    /**
     * Whether the current stage forces the actor to attach at least one
     * supporting document before the decision is accepted.
     */
    #[Computed]
    public function documentsRequired(): bool
    {
        return (bool) $this->aid->currentStage?->documents_required;
    }

    /**
     * The admin-defined labels of the document types expected at this
     * stage, shown as a checklist hint on the upload field.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function requiredDocumentLabels(): array
    {
        return array_values(array_filter(
            (array) ($this->aid->currentStage?->required_documents ?? []),
            static fn ($label): bool => filled($label),
        ));
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

        // Files must always pass the type/size gate; when the stage requires
        // documents at least one is mandatory, otherwise they are optional.
        $this->validate([
            'documents' => [$this->documentsRequired ? 'required' : 'nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'documents.required' => __('approvals.decision.documents_required'),
        ]);

        try {
            app(RecordApprovalDecision::class)->handle(
                $this->aid,
                Auth::user(),
                $action,
                $this->note !== '' ? $this->note : null,
                $this->documents,
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
