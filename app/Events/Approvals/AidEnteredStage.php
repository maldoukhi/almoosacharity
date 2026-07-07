<?php

namespace App\Events\Approvals;

use App\Models\Aid;
use App\Models\ApprovalFlowStage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever an aid becomes current at a given approval stage
 * (on submission for the first stage, and on every subsequent
 * approve-to-next-stage transition).
 */
class AidEnteredStage
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Aid $aid,
        public readonly ApprovalFlowStage $stage,
    ) {}
}
