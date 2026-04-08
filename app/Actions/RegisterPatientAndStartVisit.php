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

final class RegisterPatientAndStartVisit
{
    public function __construct(
        private readonly BranchScopedNumberGenerator $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{patient: Patient, visit: PatientVisit}
     */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $ageInputMode = (string) ($data['age_input_mode'] ?? 'dob');
            $activeBranch = BranchContext::getActiveBranch();
            $userId = Auth::id();

            $patient = Patient::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $ageInputMode === 'dob' ? ($data['date_of_birth'] ?? null) : null,
                'age' => $ageInputMode === 'age' ? ($data['age'] ?? null) : null,
                'age_units' => $ageInputMode === 'age' ? ($data['age_units'] ?? null) : null,
                'gender' => $data['gender'],
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'],
                'alternative_phone' => $data['alternative_phone'] ?? null,
                'next_of_kin_name' => $data['next_of_kin_name'] ?? null,
                'next_of_kin_phone' => $data['next_of_kin_phone'] ?? null,
                'next_of_kin_relationship' => $data['next_of_kin_relationship'] ?? null,
                'address_id' => $data['address_id'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'religion' => $data['religion'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'patient_number' => $this->numberGenerator->nextPatientNumber($activeBranch?->name),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

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
                'billing_type' => $data['billing_type'] ?? PayerType::CASH->value,
                'insurance_company_id' => ($data['billing_type'] ?? PayerType::CASH->value) === PayerType::INSURANCE->value
                    ? $data['insurance_company_id']
                    : null,
                'insurance_package_id' => ($data['billing_type'] ?? PayerType::CASH->value) === PayerType::INSURANCE->value
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

            return [
                'patient' => $patient,
                'visit' => $visit,
            ];
        });
    }
}
