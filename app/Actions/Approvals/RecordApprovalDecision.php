<?php

namespace App\Actions\Approvals;

use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Events\Aids\AidApproved;
use App\Events\Approvals\AidEnteredStage;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\ApprovalDecision;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class RecordApprovalDecision
{
    public function __construct(private readonly ResolveNextStage $resolveNextStage) {}

    /**
     * Record a decision on an aid's current approval stage and move the
     * aid forward accordingly:
     * - approve: advances to the next stage, or to Approved (final stage
     *   cleared) if there is none.
     * - reject: moves the aid to the final Rejected status.
     * - return: sends the aid back to Draft for the creator to rework
     *   (the approval_flow_id snapshot is kept for when it is resubmitted).
     *
     * Supporting documents come in two flavours:
     * - When the current stage defines required document *types*, each file
     *   is supplied labelled (['label' => ..., 'file' => UploadedFile]) via
     *   $labeledDocuments and every MANDATORY type must have a file.
     * - Otherwise, when the stage merely has documents_required = true, the
     *   actor must supply at least one anonymous file via $documents.
     * Any provided file (labelled or anonymous, required or optional) is
     * stored as media on the decision's private 'decision_documents'
     * collection; labelled files carry their document label as a media
     * custom property so which file is which stays recoverable.
     *
     * @param  array<int, UploadedFile>  $documents
     * @param  array<int, array{label: string, file: UploadedFile}>  $labeledDocuments
     *
     * @throws InvalidAidTransitionException
     * @throws InvalidArgumentException
     * @throws AuthorizationException
     */
    public function handle(Aid $aid, User $actor, ApprovalAction $action, ?string $note = null, array $documents = [], array $labeledDocuments = []): Aid
    {
        if ($aid->status !== AidStatus::UnderReview || ! $aid->current_stage_id) {
            throw InvalidAidTransitionException::notUnderReview();
        }

        Gate::forUser($actor)->authorize('act', $aid);

        $stage = $aid->currentStage;

        if (! $stage->allows($action)) {
            throw new InvalidArgumentException(__('validation.custom.approval.action_not_allowed'));
        }

        if ($action->requiresNote() && blank($note)) {
            throw new InvalidArgumentException(__('validation.custom.approval.note_required'));
        }

        $documents = array_values(array_filter(
            $documents,
            static fn ($file): bool => $file instanceof UploadedFile,
        ));

        $labeledDocuments = array_values(array_filter(
            $labeledDocuments,
            static fn ($entry): bool => is_array($entry) && ($entry['file'] ?? null) instanceof UploadedFile,
        ));

        $documentTypes = $stage->requiredDocumentTypes();

        if ($documentTypes !== []) {
            // Stage defines document *types*: every mandatory type must have a
            // labelled file. Optional types may be omitted.
            foreach ($documentTypes as $type) {
                if (! $type['required']) {
                    continue;
                }

                $satisfied = false;

                foreach ($labeledDocuments as $entry) {
                    if ((string) $entry['label'] === $type['label']) {
                        $satisfied = true;

                        break;
                    }
                }

                if (! $satisfied) {
                    throw new InvalidArgumentException(__('approvals.decision.documents_required'));
                }
            }
        } elseif ($stage->documents_required && $documents === []) {
            // Stage requires documents but names no specific types: at least
            // one anonymous file is mandatory.
            throw new InvalidArgumentException(__('approvals.decision.documents_required'));
        }

        return DB::transaction(function () use ($aid, $actor, $action, $note, $stage, $documents, $labeledDocuments): Aid {
            // Re-read under a row lock: two concurrent decisions on the same
            // aid would otherwise both pass the pre-transaction status check
            // and record conflicting decisions.
            $locked = Aid::query()->whereKey($aid->id)->lockForUpdate()->first();

            if ($locked->status !== AidStatus::UnderReview || $locked->current_stage_id !== $stage->id) {
                throw InvalidAidTransitionException::notUnderReview();
            }

            $decision = ApprovalDecision::create([
                'aid_id' => $aid->id,
                'approval_flow_stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'user_id' => $actor->id,
                'action' => $action->value,
                'note' => $note,
                'decided_at' => now(),
            ]);

            foreach ($labeledDocuments as $entry) {
                $file = $entry['file'];

                $decision
                    ->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->withCustomProperties(['document_label' => (string) $entry['label']])
                    ->toMediaCollection('decision_documents');
            }

            foreach ($documents as $file) {
                $decision
                    ->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->toMediaCollection('decision_documents');
            }

            match ($action) {
                ApprovalAction::Approve => $this->applyApprove($aid),
                ApprovalAction::Reject => $this->applyReject($aid),
                ApprovalAction::Return => $this->applyReturn($aid),
            };

            return $aid->fresh();
        });
    }

    private function applyApprove(Aid $aid): void
    {
        $next = $this->resolveNextStage->handle($aid);

        if ($next) {
            $aid->update(['current_stage_id' => $next->id]);

            event(new AidEnteredStage($aid->refresh(), $next));

            return;
        }

        $this->assertTransition($aid, AidStatus::Approved);

        $aid->update([
            'status' => AidStatus::Approved,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);

        event(new AidApproved($aid->refresh()));
    }

    private function applyReject(Aid $aid): void
    {
        $this->assertTransition($aid, AidStatus::Rejected);

        $aid->update([
            'status' => AidStatus::Rejected,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);
    }

    private function applyReturn(Aid $aid): void
    {
        $this->assertTransition($aid, AidStatus::Draft);

        $aid->update([
            'status' => AidStatus::Draft,
            'current_stage_id' => null,
        ]);
    }

    /**
     * @throws InvalidAidTransitionException
     */
    private function assertTransition(Aid $aid, AidStatus $to): void
    {
        if (! $aid->status->canTransitionTo($to)) {
            throw InvalidAidTransitionException::notUnderReview();
        }
    }
}
