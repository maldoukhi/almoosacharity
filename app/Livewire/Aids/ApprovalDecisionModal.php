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

    /**
     * Anonymous supporting files — used only when the current stage requires
     * documents but names no specific document types.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $documents = [];

    /**
     * Per-document-type uploads, keyed by the document type's index in
     * {@see self::documentTypes()}. Each slot holds a single file.
     *
     * @var array<int, TemporaryUploadedFile|null>
     */
    public array $typedDocuments = [];

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
     * The admin-defined document types expected at this stage, each with its
     * label and whether it is mandatory. When non-empty the modal renders a
     * labelled file slot per type instead of a single anonymous field.
     *
     * @return array<int, array{label: string, required: bool}>
     */
    #[Computed]
    public function documentTypes(): array
    {
        return $this->aid->currentStage?->requiredDocumentTypes() ?? [];
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

        $documentTypes = $this->documentTypes();
        $labeledDocuments = [];

        if ($documentTypes !== []) {
            // Per-type slots: mandatory types must be filled, optional ones may
            // be empty; every provided file passes the type/size gate.
            $rules = [];
            $messages = [];

            foreach ($documentTypes as $i => $type) {
                $rules["typedDocuments.{$i}"] = [$type['required'] ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
                $messages["typedDocuments.{$i}.required"] = __('approvals.decision.document_slot_required', ['label' => $type['label']]);
            }

            $this->validate($rules, $messages);

            foreach ($documentTypes as $i => $type) {
                $file = $this->typedDocuments[$i] ?? null;

                if ($file instanceof TemporaryUploadedFile) {
                    $labeledDocuments[] = ['label' => $type['label'], 'file' => $file];
                }
            }
        } else {
            // No named types: a single anonymous multi-file field. When the
            // stage requires documents at least one is mandatory.
            $this->validate([
                'documents' => [$this->documentsRequired ? 'required' : 'nullable', 'array'],
                'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ], [
                'documents.required' => __('approvals.decision.documents_required'),
            ]);
        }

        try {
            app(RecordApprovalDecision::class)->handle(
                $this->aid,
                Auth::user(),
                $action,
                $this->note !== '' ? $this->note : null,
                $documentTypes === [] ? $this->documents : [],
                $labeledDocuments,
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
