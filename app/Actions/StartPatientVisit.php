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

final class StartPatientVisit
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(Patient $patient, array $data): PatientVisit
    {
        /** @var PatientVisit */
        return DB::transaction(static function () use ($patient, $data): PatientVisit {
            $activeBranch = BranchContext::getActiveBranch();
            $prefix = $activeBranch ? strtoupper(mb_substr($activeBranch->name, 0, 3)) : 'VIS';
            $userId = Auth::id();

            $latest = PatientVisit::query()
                ->where('visit_number', 'like', sprintf('%s-%%', $prefix))
                ->lockForUpdate()
                ->latest('visit_number')
                ->value('visit_number');

            $nextNumber = 1;
            if (is_string($latest) && preg_match('/^(?<prefix>[A-Z]+)-(?<num>\d+)$/', $latest, $matches) === 1) {
                $nextNumber = ((int) $matches['num']) + 1;
            }

            $visit = PatientVisit::create([
                'tenant_id' => $patient->tenant_id,
                'patient_id' => $patient->id,
                'facility_branch_id' => $activeBranch?->id,
                'visit_number' => sprintf('%s-%06d', $prefix, $nextNumber),
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

            return $visit;
        });
    }
}
