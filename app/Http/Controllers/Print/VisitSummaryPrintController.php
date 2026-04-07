<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Enums\FacilityServiceOrderStatus;
use App\Enums\ImagingRequestStatus;
use App\Enums\LabRequestStatus;
use App\Enums\PrescriptionStatus;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class VisitSummaryPrintController implements HasMiddleware
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

    public function show(PatientVisit $visit): Response
    {
        $this->activeBranchWorkspace->authorizeModel($visit);

        $visit->load([
            'patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number,email,blood_group',
            'branch:id,name,branch_code',
            'clinic:id,clinic_name',
            'doctor:id,first_name,last_name',
            'payer:id,patient_visit_id,billing_type,insurance_company_id,insurance_package_id',
            'payer.insuranceCompany:id,name',
            'payer.insurancePackage:id,name',
            'billing:id,patient_visit_id,visit_payer_id,payer_type,gross_amount,discount_amount,paid_amount,balance_amount,status,billed_at,settled_at',
            'billing.payments' => static fn (HasMany $query): HasMany => $query
                ->select('id', 'visit_billing_id', 'patient_visit_id', 'receipt_number', 'payment_date', 'amount', 'payment_method', 'reference_number', 'is_refund', 'notes')
                ->latest('payment_date'),
            'triage:id,visit_id,nurse_id,triage_datetime,triage_grade,attendance_type,news_score,pews_score,conscious_level,mobility_status,chief_complaint,history_of_presenting_illness,nurse_notes',
            'triage.nurse:id,first_name,last_name',
            'triage.vitalSigns' => static fn (HasMany $query): HasMany => $query
                ->with(['recordedBy:id,first_name,last_name'])
                ->latest('recorded_at'),
            'consultation:id,visit_id,doctor_id,started_at,completed_at,chief_complaint,history_of_present_illness,objective_findings,assessment,plan,primary_diagnosis,primary_icd10_code',
            'consultation.doctor:id,first_name,last_name',
            'labRequests' => static fn (HasMany $query): HasMany => $query
                ->with(['items.test:id,test_name,test_code'])
                ->whereNotIn('status', [
                    LabRequestStatus::CANCELLED->value,
                    LabRequestStatus::REJECTED->value,
                ])
                ->latest('request_date'),
            'imagingRequests' => static fn (HasMany $query): HasMany => $query
                ->whereNotIn('status', [
                    ImagingRequestStatus::CANCELLED->value,
                ])
                ->latest(),
            'prescriptions' => static fn (HasMany $query): HasMany => $query
                ->with([
                    'items.inventoryItem:id,generic_name,brand_name,strength,dosage_form',
                    'prescribedBy:id,first_name,last_name',
                ])
                ->whereNotIn('status', [
                    PrescriptionStatus::CANCELLED->value,
                ])
                ->latest('prescription_date'),
            'facilityServiceOrders' => static fn (HasMany $query): HasMany => $query
                ->with([
                    'service:id,name,service_code',
                    'orderedBy:id,first_name,last_name',
                ])
                ->whereNotIn('status', [
                    FacilityServiceOrderStatus::CANCELLED->value,
                ])
                ->latest('ordered_at'),
        ]);

        $pdf = Pdf::loadView('print.visit-summary', [
            'visit' => $visit,
            'printedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream(sprintf(
            'visit-summary-%s.pdf',
            $visit->visit_number,
        ));
    }
}
