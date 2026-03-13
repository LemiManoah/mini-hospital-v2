<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class RegisterPatientAndStartVisit
{
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

            $patient = Patient::create([
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
                'patient_number' => self::generatePatientNumber(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $visit = PatientVisit::create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'facility_branch_id' => $activeBranch?->id,
                'visit_number' => self::generateVisitNumber($activeBranch?->name),
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

            VisitPayer::create([
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

            return [
                'patient' => $patient,
                'visit' => $visit,
            ];
        });
    }

    private static function generatePatientNumber(): string
    {
        $activeBranch = BranchContext::getActiveBranch();
        $prefix = self::branchInitials($activeBranch?->name ?? null, 'HSP');

        $latest = Patient::query()
            ->where('patient_number', 'like', sprintf('%s-%%', $prefix))
            ->lockForUpdate()
            ->latest('patient_number')
            ->value('patient_number');

        $nextNumber = 1;
        if (is_string($latest) && preg_match('/^(?<prefix>[A-Z]+)-(?<num>\d+)$/', $latest, $matches) === 1) {
            $nextNumber = ((int) $matches['num']) + 1;
        }

        return sprintf('%s-%06d', $prefix, $nextNumber);
    }

    private static function generateVisitNumber(?string $branchName): string
    {
        $prefix = self::branchInitials($branchName, 'VIS');

        $latest = PatientVisit::query()
            ->where('visit_number', 'like', sprintf('%s-%%', $prefix))
            ->lockForUpdate()
            ->latest('visit_number')
            ->value('visit_number');

        $nextNumber = 1;
        if (is_string($latest) && preg_match('/^(?<prefix>[A-Z]+)-(?<num>\d+)$/', $latest, $matches) === 1) {
            $nextNumber = ((int) $matches['num']) + 1;
        }

        return sprintf('%s-%06d', $prefix, $nextNumber);
    }

    private static function branchInitials(?string $branchName, string $fallback): string
    {
        $name = mb_strtoupper(trim((string) $branchName));
        if ($name === '') {
            return $fallback;
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= mb_substr($part, 0, 1);

            if (mb_strlen($initials) >= 3) {
                break;
            }
        }

        return str_pad(mb_substr($initials, 0, 3), 3, 'X');
    }
}
