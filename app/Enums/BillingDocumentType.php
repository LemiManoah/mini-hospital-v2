<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingDocumentType: string
{
    case PatientReceipt = 'patient_receipt';
    case InsuranceInvoice = 'insurance_invoice';
    case DepositReceipt = 'deposit_receipt';

    public function defaultPrefix(): string
    {
        return match ($this) {
            self::PatientReceipt => 'RCT',
            self::InsuranceInvoice => 'ICI',
            self::DepositReceipt => 'DEP',
        };
    }
}
