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

        $data['map'] = $this->meanArterialPressure(
            $data['systolic_bp'] ?? null,
            $data['diastolic_bp'] ?? null,
        );
        $data['bmi'] = $this->bodyMassIndex(
            $data['height_cm'] ?? null,
            $data['weight_kg'] ?? null,
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
}
