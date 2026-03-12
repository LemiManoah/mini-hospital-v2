<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PayerType;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;

final class CreatePatient
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Patient
    {
        return DB::transaction(static function () use ($data): Patient {
            $ageInputMode = (string) ($data['age_input_mode'] ?? 'dob');

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
                'default_payer_type' => $data['payer_type'],
                'patient_number' => self::generatePatientNumber(),
            ]);

            if (($data['payer_type'] ?? PayerType::CASH->value) === PayerType::INSURANCE->value) {
                PatientInsurance::query()->updateOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'insurance_company_id' => $data['insurance_company_id'],
                        'insurance_package_id' => $data['insurance_package_id'],
                    ],
                    []
                );
            }

            return $patient;
        });
    }

    private static function generatePatientNumber(): string
    {
        $activeBranch = BranchContext::getActiveBranch();
        $prefix = self::branchInitials($activeBranch?->name ?? null);

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

    private static function branchInitials(?string $branchName): string
    {
        $name = mb_strtoupper(trim((string) $branchName));
        if ($name === '') {
            return 'HSP';
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
