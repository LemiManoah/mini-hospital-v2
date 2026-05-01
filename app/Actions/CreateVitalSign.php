<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateVitalSignDTO;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Support\Facades\Auth;

final readonly class CreateVitalSign
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(PatientVisit $visit, CreateVitalSignDTO $data): VitalSign
    {
        $triage = $visit->triage;
        $staffId = Auth::user()?->staff_id;

        $vitalSign = VitalSign::query()->create([
            'temperature' => $data->temperature,
            'temperature_unit' => $data->temperatureUnit,
            'pulse_rate' => $data->pulseRate,
            'respiratory_rate' => $data->respiratoryRate,
            'systolic_bp' => $data->systolicBp,
            'diastolic_bp' => $data->diastolicBp,
            'oxygen_saturation' => $data->oxygenSaturation,
            'on_supplemental_oxygen' => $data->onSupplementalOxygen,
            'oxygen_delivery_method' => $data->oxygenDeliveryMethod,
            'oxygen_flow_rate' => $data->oxygenFlowRate,
            'blood_glucose' => $data->bloodGlucose,
            'blood_glucose_unit' => $data->bloodGlucoseUnit,
            'pain_score' => $data->painScore,
            'height_cm' => $data->heightCm,
            'weight_kg' => $data->weightKg,
            'head_circumference_cm' => $data->headCircumferenceCm,
            'chest_circumference_cm' => $data->chestCircumferenceCm,
            'muac_cm' => $data->muacCm,
            'capillary_refill' => $data->capillaryRefill,
            'map' => $data->meanArterialPressure(),
            'bmi' => $data->bodyMassIndex(),
            'triage_id' => $triage?->id,
            'recorded_at' => now(),
            'recorded_by' => $staffId,
        ]);

        $user = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'clinical',
            event: 'vital_sign.recorded',
            subject: $vitalSign,
            description: 'Vital signs recorded.',
            tenantId: $visit->tenant_id,
            branchId: $visit->facility_branch_id,
            staffId: $staffId,
            newValues: [
                'visit_id' => $visit->id,
                'triage_id' => $triage?->id,
                'vital_sign_id' => $vitalSign->id,
                'recorded_at' => $vitalSign->recorded_at?->toISOString(),
            ],
            metadata: [
                'causer_user_id' => $user instanceof User ? $user->id : null,
            ],
        );

        return $vitalSign;
    }
}
