<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Enums\PayerType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\PatientVisit;
use App\Support\BranchScopedNumberGenerator;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CheckInAppointment
{
    public function __construct(
        private BranchScopedNumberGenerator $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Appointment $appointment, array $attributes): PatientVisit
    {
        /** @var PatientVisit */
        return DB::transaction(function () use ($appointment, $attributes): PatientVisit {
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
            $userId = Auth::id();

            $visit = PatientVisit::query()->create([
                'tenant_id' => $appointment->tenant_id,
                'patient_id' => $appointment->patient_id,
                'facility_branch_id' => $appointment->facility_branch_id ?? $activeBranch?->id,
                'visit_number' => $this->numberGenerator->nextVisitNumber($activeBranch?->name),
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

            $payer = VisitPayer::query()->create([
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

            VisitBilling::query()->create([
                'tenant_id' => $appointment->tenant_id,
                'facility_branch_id' => $visit->facility_branch_id,
                'patient_visit_id' => $visit->id,
                'visit_payer_id' => $payer->id,
                'payer_type' => $payer->billing_type,
                'insurance_company_id' => $payer->insurance_company_id,
                'insurance_package_id' => $payer->insurance_package_id,
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
