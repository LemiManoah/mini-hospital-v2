<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\CreatePatientRegistrationDTO;
use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use App\Support\BranchScopedNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class RegisterPatientAndStartVisit
{
    public function __construct(
        private BranchScopedNumberGenerator $numberGenerator,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    /**
     * @return array{patient: Patient, visit: PatientVisit}
     */
    public function handle(CreatePatientRegistrationDTO $data): array
    {
        return DB::transaction(function () use ($data): array {
            $activeBranch = BranchContext::getActiveBranch();
            /** @var User|null $authenticatedUser */
            $authenticatedUser = Auth::user();
            $userId = Auth::id();

            $patient = Patient::query()->create([
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'middle_name' => $data->middleName,
                'date_of_birth' => $data->ageInputMode === 'dob' ? $data->dateOfBirth : null,
                'age' => $data->ageInputMode === 'age' ? $data->age : null,
                'age_units' => $data->ageInputMode === 'age' ? $data->ageUnits : null,
                'gender' => $data->gender,
                'email' => $data->email,
                'phone_number' => $data->phoneNumber,
                'alternative_phone' => $data->alternativePhone,
                'next_of_kin_name' => $data->nextOfKinName,
                'next_of_kin_phone' => $data->nextOfKinPhone,
                'next_of_kin_relationship' => $data->nextOfKinRelationship,
                'address_id' => $data->addressId,
                'marital_status' => $data->maritalStatus,
                'occupation' => $data->occupation,
                'religion' => $data->religion,
                'country_id' => $data->countryId,
                'blood_group' => $data->bloodGroup,
                'patient_number' => $this->numberGenerator->nextPatientNumber(
                    $activeBranch?->branch_code,
                    is_string($authenticatedUser?->tenant_id) ? $authenticatedUser->tenant_id : '',
                ),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $visit = PatientVisit::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'facility_branch_id' => $activeBranch?->id,
                'visit_number' => $this->numberGenerator->nextVisitNumber($activeBranch?->branch_code),
                'visit_type' => $data->visitType,
                'status' => VisitStatus::REGISTERED,
                'clinic_id' => $data->clinicId,
                'doctor_id' => $data->doctorId,
                'is_emergency' => $data->isEmergency,
                'registered_at' => now(),
                'registered_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $payer = VisitPayer::query()->create([
                'tenant_id' => $patient->tenant_id,
                'patient_visit_id' => $visit->id,
                'billing_type' => $data->billingType,
                'insurance_company_id' => $data->billingType === PayerType::INSURANCE->value
                    ? $data->insuranceCompanyId
                    : null,
                'insurance_package_id' => $data->billingType === PayerType::INSURANCE->value
                    ? $data->insurancePackageId
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

            $this->recordAuditActivity->handle(
                logName: 'clinical',
                event: 'patient.registered',
                subject: $patient,
                description: 'Patient registered.',
                tenantId: $patient->tenant_id,
                branchId: $activeBranch?->id,
                staffId: $authenticatedUser?->staffId(),
                newValues: [
                    'patient_id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                ],
            );

            $this->recordAuditActivity->handle(
                logName: 'clinical',
                event: 'visit.started',
                subject: $visit,
                description: 'Patient visit started.',
                tenantId: $visit->tenant_id,
                branchId: $visit->facility_branch_id,
                staffId: $authenticatedUser?->staffId(),
                newValues: [
                    'visit_id' => $visit->id,
                    'visit_number' => $visit->visit_number,
                    'patient_id' => $patient->id,
                    'status' => VisitStatus::REGISTERED->value,
                    'registered_at' => $visit->registered_at?->toISOString(),
                ],
            );

            return [
                'patient' => $patient,
                'visit' => $visit,
            ];
        });
    }
}
