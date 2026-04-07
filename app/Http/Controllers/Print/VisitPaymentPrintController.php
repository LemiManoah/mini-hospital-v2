<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Models\PatientVisit;
use App\Models\Payment;
use App\Support\ActiveBranchWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class VisitPaymentPrintController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['show']),
        ];
    }

    public function show(PatientVisit $visit, Payment $payment): Response
    {
        $this->activeBranchWorkspace->authorizeModel($visit);

        abort_unless(
            $payment->patient_visit_id === $visit->id,
            404,
            'Payment receipt not found for this visit.',
        );

        abort_if(
            $payment->is_refund,
            403,
            'Refund transactions cannot be printed as payment receipts.',
        );

        $payment->loadMissing([
            'visit:id,patient_id,facility_branch_id,visit_number',
            'visit.patient:id,patient_number,first_name,last_name,middle_name,phone_number',
            'visit.branch:id,name,branch_code,currency_id',
            'visit.branch.currency:id,code,symbol',
            'visit.payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'visit.payer.insuranceCompany:id,name',
            'visit.payer.insurancePackage:id,name',
            'billing:id,patient_visit_id,status,gross_amount,paid_amount,balance_amount',
        ]);

        $pdf = Pdf::loadView('print.payment-receipt', [
            'payment' => $payment,
            'visit' => $payment->visit,
            'patient' => $payment->visit?->patient,
            'branch' => $payment->visit?->branch,
            'payer' => $payment->visit?->payer,
            'billing' => $payment->billing,
            'printedAt' => now(),
        ])->setPaper('a4');

        $filename = sprintf(
            'payment-receipt-%s.pdf',
            $payment->receipt_number ?: $visit->visit_number,
        );

        return $pdf->stream($filename);
    }
}
