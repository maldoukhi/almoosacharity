<?php

namespace App\Actions\BeneficiaryFlows;

use App\Models\Beneficiary;
use App\Models\BeneficiaryFlowStage;

class ResolveNextBeneficiaryStage
{
    /**
     * The next stage (by order) after the beneficiary's current stage within
     * its snapshotted flow, or null if the current stage is the last one.
     */
    public function handle(Beneficiary $beneficiary): ?BeneficiaryFlowStage
    {
        if (! $beneficiary->beneficiary_flow_id || ! $beneficiary->currentStage) {
            return null;
        }

        return BeneficiaryFlowStage::query()
            ->where('beneficiary_flow_id', $beneficiary->beneficiary_flow_id)
            ->where('order', '>', $beneficiary->currentStage->order)
            ->orderBy('order')
            ->first();
    }
}
