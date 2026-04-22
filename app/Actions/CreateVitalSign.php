<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateVitalSignDTO;
use App\Models\PatientVisit;
use App\Models\VitalSign;
use Illuminate\Support\Facades\Auth;

final readonly class CreateVitalSign
{
    public function handle(PatientVisit $visit, CreateVitalSignDTO $data): VitalSign
    {
        $triage = $visit->triage;
        $staffId = Auth::user()?->staff_id;

        return VitalSign::query()->create([
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
    }
}
