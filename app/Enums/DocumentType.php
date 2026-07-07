<?php

namespace App\Enums;

enum DocumentType: string
{
    case NationalIdDoc = 'national_id_doc';
    case PropertyDeed = 'property_deed';
    case RentContract = 'rent_contract';
    case MedicalReport = 'medical_report';
    case IncomeProof = 'income_proof';
    case Other = 'other';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.document_type.'.$this->value);
    }
}
