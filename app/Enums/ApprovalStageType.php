<?php

namespace App\Enums;

/**
 * The kind of work a single approval-flow stage represents. Beyond the
 * classic approve/reject decision, a flow can require a document to be
 * uploaded, or wait for the beneficiary's own response, before moving on.
 */
enum ApprovalStageType: string
{
    case Approval = 'approval';
    case DocumentUpload = 'document_upload';
    case BeneficiaryResponse = 'beneficiary_response';

    public function label(): string
    {
        return __('approvals.stage_type.'.$this->value);
    }
}
