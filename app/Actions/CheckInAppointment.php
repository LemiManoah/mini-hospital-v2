<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\FacilityBranch;
use App\Models\PatientVisit;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CheckInAppointment
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Appointment $appointment, array $attributes): PatientVisit
    {
        /** @var PatientVisit */
        return DB::transaction(static function () use ($appointment, $attributes): PatientVisit {
            if ($appointment->visit()->exists()) {
                throw ValidationException::withMessages([
                    'appointment' => 'This appointment has already been checked in.',
                ]);
            }

            if ($appointment->isTerminal()) {
                throw ValidationException::withMessages([
                    'appointment' => 'This appointment can no longer be checked in.',
                ]);
            }

            $activeVisitExists = $appointment->patient->visits()
                ->whereNotIn('status', [VisitStatus::COMPLETED->value, VisitStatus::CANCELLED->value])
                ->exists();

            if ($activeVisitExists) {
                throw ValidationException::withMessages([
                    'appointment' => 'Patient already has an active visit. Please complete or cancel it first.',
                ]);
            }

            $activeBranch = BranchContext::getActiveBranch();
            $prefix = $activeBranch instanceof FacilityBranch ? mb_strtoupper(mb_substr($activeBranch->name, 0, 3)) : 'VIS';
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

            $visit = PatientVisit::query()->create([
                'tenant_id' => $appointment->tenant_id,
                'patient_id' => $appointment->patient_id,
                'facility_branch_id' => $appointment->facility_branch_id ?? $activeBranch?->id,
                'visit_number' => sprintf('%s-%06d', $prefix, $nextNumber),
                'visit_type' => $attributes['visit_type'] ?? VisitType::OPD_CONSULTATION->value,
                'status' => VisitStatus::REGISTERED,
                'clinic_id' => $appointment->clinic_id,
                'doctor_id' => $appointment->doctor_id,
                'appointment_id' => $appointment->id,
                'is_emergency' => ! empty($attributes['is_emergency']),
                'registered_at' => now(),
                'registered_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            VisitPayer::query()->create([
                'tenant_id' => $appointment->tenant_id,
                'patient_visit_id' => $visit->id,
                'billing_type' => $attributes['billing_type'],
                'insurance_company_id' => $attributes['billing_type'] === PayerType::INSURANCE->value
                    ? $attributes['insurance_company_id']
                    : null,
                'insurance_package_id' => $attributes['billing_type'] === PayerType::INSURANCE->value
                    ? $attributes['insurance_package_id']
                    : null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $appointment->update([
                'status' => AppointmentStatus::CHECKED_IN,
                'checked_in_at' => now(),
                'updated_by' => $userId,
            ]);

            return $visit;
        });
    }
}
