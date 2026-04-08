<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Support\BranchScopedNumberGenerator;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class StartPatientVisit
{
    public function __construct(
        private readonly BranchScopedNumberGenerator $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): PatientVisit
    {
        /** @var PatientVisit */
        return DB::transaction(function () use ($patient, $data): PatientVisit {
            $activeBranch = BranchContext::getActiveBranch();
            $userId = Auth::id();

            $visit = PatientVisit::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'facility_branch_id' => $activeBranch?->id,
                'visit_number' => $this->numberGenerator->nextVisitNumber($activeBranch?->name),
                'visit_type' => $data['visit_type'],
                'status' => VisitStatus::REGISTERED,
                'clinic_id' => $data['clinic_id'] ?? null,
                'doctor_id' => $data['doctor_id'] ?? null,
                'is_emergency' => ! empty($data['is_emergency']),
                'registered_at' => now(),
                'registered_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $payer = VisitPayer::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_visit_id' => $visit->id,
                'billing_type' => $data['billing_type'],
                'insurance_company_id' => $data['billing_type'] === PayerType::INSURANCE->value
                    ? $data['insurance_company_id']
                    : null,
                'insurance_package_id' => $data['billing_type'] === PayerType::INSURANCE->value
                    ? $data['insurance_package_id']
                    : null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            VisitBilling::query()->create([
                'tenant_id' => $patient->tenant_id,
                'facility_branch_id' => $activeBranch?->id,
                'patient_visit_id' => $visit->id,
                'visit_payer_id' => $payer->id,
                'payer_type' => $payer->billing_type,
                'insurance_company_id' => $payer->insurance_company_id,
                'insurance_package_id' => $payer->insurance_package_id,
            ]);

            return $visit;
        });
    }
}
