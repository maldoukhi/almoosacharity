<?php

namespace App\Enums;

use App\Models\ApprovalFlowStage;

/**
 * The kind of work a single approval-flow stage represents. Either a
 * classic approve/reject staff decision, or a stage that waits for the
 * beneficiary's own response (submitted from a public link) before moving
 * on. Document collection is NOT a separate type: it is expressed by an
 * Approval stage with documents_required = true (see
 * {@see ApprovalFlowStage::$documents_required}).
 */
enum ApprovalStageType: string
{
    case Approval = 'approval';
    case BeneficiaryResponse = 'beneficiary_response';

    public function label(): string
    {
        return __('approvals.stage_type.'.$this->value);
    }
}
