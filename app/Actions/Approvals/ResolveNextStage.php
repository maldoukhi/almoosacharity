<?php

namespace App\Actions\Approvals;

use App\Models\Aid;
use App\Models\ApprovalFlowStage;

class ResolveNextStage
{
    /**
     * The next stage (by order) after the aid's current stage within its
     * snapshotted approval flow, or null if the current stage is the last
     * one.
     */
    public function handle(Aid $aid): ?ApprovalFlowStage
    {
        if (! $aid->approval_flow_id || ! $aid->currentStage) {
            return null;
        }

        return ApprovalFlowStage::query()
            ->where('approval_flow_id', $aid->approval_flow_id)
            ->where('order', '>', $aid->currentStage->order)
            ->orderBy('order')
            ->first();
    }
}
