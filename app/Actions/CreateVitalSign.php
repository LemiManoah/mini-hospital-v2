<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PatientVisit;
use App\Models\VitalSign;
use Illuminate\Support\Facades\Auth;

final readonly class CreateVitalSign
{
    public function handle(PatientVisit $visit, array $data): VitalSign
    {
        $triage = $visit->triage;
        $staffId = Auth::user()?->staff_id;

        $systolicBp = $this->nullableInt($data['systolic_bp'] ?? null);
        $diastolicBp = $this->nullableInt($data['diastolic_bp'] ?? null);
        $heightCm = $this->nullableFloat($data['height_cm'] ?? null);
        $weightKg = $this->nullableFloat($data['weight_kg'] ?? null);

        $data['temperature_unit'] = $data['temperature_unit'] ?? 'celsius';
        $data['blood_glucose_unit'] = $data['blood_glucose_unit'] ?? 'mg_dl';
        $data['systolic_bp'] = $systolicBp;
        $data['diastolic_bp'] = $diastolicBp;
        $data['height_cm'] = $heightCm;
        $data['weight_kg'] = $weightKg;
        $data['map'] = $this->meanArterialPressure(
            $systolicBp,
            $diastolicBp,
        );
        $data['bmi'] = $this->bodyMassIndex(
            $heightCm,
            $weightKg,
        );

        return VitalSign::query()->create([
            ...$data,
            'triage_id' => $triage->id,
            'recorded_at' => now(),
            'on_supplemental_oxygen' => ! empty($data['on_supplemental_oxygen']),
            'recorded_by' => $staffId,
        ]);
    }

    private function meanArterialPressure(?int $systolic, ?int $diastolic): ?int
    {
        if ($systolic === null || $diastolic === null) {
            return null;
        }

        return (int) round(($systolic + (2 * $diastolic)) / 3);
    }

    private function bodyMassIndex(mixed $heightCm, mixed $weightKg): ?float
    {
        if (! is_numeric($heightCm) || ! is_numeric($weightKg)) {
            return null;
        }

        $heightMeters = ((float) $heightCm) / 100;
        if ($heightMeters <= 0.0) {
            return null;
        }

        return round(((float) $weightKg) / ($heightMeters * $heightMeters), 2);
    }

    private function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
