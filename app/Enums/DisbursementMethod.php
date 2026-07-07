<?php

namespace App\Enums;

enum DisbursementMethod: string
{
    case BankTransfer = 'bank_transfer';
    case OfficePickup = 'office_pickup';
    case Courier = 'courier';
    case FieldHandover = 'field_handover';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('disbursements.method.'.$this->value);
    }

    /**
     * Only a bank transfer requires the beneficiary to already have a
     * registered IBAN on file before disbursement can start.
     */
    public function requiresBankAccount(): bool
    {
        return $this === self::BankTransfer;
    }

    /**
     * Translation key for the label of the delivery-record reference field
     * appropriate to this method: a bank transfer records a transfer
     * reference, every other method records a receipt number instead.
     */
    public function referenceLabelKey(): string
    {
        return match ($this) {
            self::BankTransfer => 'disbursements.field_transfer_reference',
            self::OfficePickup, self::Courier, self::FieldHandover => 'disbursements.field_receipt_number',
        };
    }
}
