<?php

namespace App\Actions\Approvals;

use App\Enums\AidStatus;
use App\Models\Aid;
use App\Models\BeneficiaryStageResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records the beneficiary's own response to a beneficiary_response approval
 * stage, submitted from the public link, then advances the aid to the next
 * stage exactly as a staff approve would.
 *
 * Idempotent and single-use: a second submission on an already-answered
 * link is a silent no-op (the row's responded_at guards it under a lock).
 * Also verifies the aid is still under review at this very stage before
 * advancing, so a stale link (the stage already moved on) cannot double-
 * advance the workflow.
 */
class RecordBeneficiaryStageResponse
{
    public function __construct(private readonly RecordApprovalDecision $recordApprovalDecision) {}

    public function handle(
        BeneficiaryStageResponse $response,
        string $ip,
        string $userAgent,
        ?string $note = null,
        ?UploadedFile $document = null,
    ): void {
        DB::transaction(function () use ($response, $ip, $userAgent, $note, $document): void {
            $locked = BeneficiaryStageResponse::query()->whereKey($response->id)->lockForUpdate()->first();

            if ($locked === null || $locked->responded_at !== null) {
                // Already answered (or the row vanished): nothing to do.
                return;
            }

            $note = trim((string) $note);

            $locked->update([
                'responded_at' => now(),
                'responded_ip' => $ip,
                'responded_user_agent' => mb_substr($userAgent, 0, 1000),
                'note' => $note === '' ? null : mb_substr($note, 0, 2000),
            ]);

            $this->attachDocument($locked, $document);

            $aid = Aid::query()->whereKey($locked->aid_id)->lockForUpdate()->first();

            // Only advance when the aid is genuinely still parked at this
            // stage. If it already moved on, the response is recorded but the
            // workflow is left untouched (a stale link must not re-advance).
            if (
                $aid !== null
                && $aid->status === AidStatus::UnderReview
                && (int) $aid->current_stage_id === (int) $locked->approval_flow_stage_id
            ) {
                $this->recordApprovalDecision->advance($aid);
            }

            activity('beneficiary-stage-response')
                ->performedOn($locked)
                ->log('beneficiary submitted stage response');
        });
    }

    /**
     * Persist the beneficiary's uploaded document onto the response record,
     * if one was provided. Any store failure is logged and swallowed: a bad
     * upload must never fail the response itself, which is the meaningful
     * action. The public component already gates mime/size before this runs.
     */
    private function attachDocument(BeneficiaryStageResponse $response, ?UploadedFile $document): void
    {
        if (! $document instanceof UploadedFile) {
            return;
        }

        try {
            $response
                ->addMedia($document->getRealPath())
                ->usingName($document->getClientOriginalName())
                ->toMediaCollection('beneficiary_stage_document');
        } catch (\Throwable $e) {
            Log::warning('Failed to store beneficiary stage-response document.', [
                'beneficiary_stage_response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
