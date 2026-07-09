<?php

namespace App\Listeners;

use App\Actions\Approvals\RequestBeneficiaryStageResponse;
use App\Events\Approvals\AidEnteredStage;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * When an aid becomes current at a beneficiary_response stage — whether on
 * submission (as stage 1) or after a staff approve advances it there — the
 * decision no longer belongs to staff: issue the beneficiary a single-
 * purpose public link to submit their own response. Classic approval
 * stages are ignored here (staff are handled by {@see NotifyStageApprovers}).
 *
 * Queued and idempotent: re-firing simply rotates the link's token.
 */
class IssueBeneficiaryStageLink implements ShouldQueue
{
    public function __construct(private readonly RequestBeneficiaryStageResponse $request) {}

    public function handle(AidEnteredStage $event): void
    {
        if (! $event->stage->isBeneficiaryResponse()) {
            return;
        }

        $this->request->handle($event->aid, $event->stage);
    }
}
