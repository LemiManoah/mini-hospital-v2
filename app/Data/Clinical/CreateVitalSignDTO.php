<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateVitalSignDTO
{
    public function __construct(
        public ?float $temperature,
        public string $temperatureUnit,
        public ?int $pulseRate,
        public ?int $respiratoryRate,
        public ?int $systolicBp,
        public ?int $diastolicBp,
        public ?float $oxygenSaturation,
        public bool $onSupplementalOxygen,
        public ?string $oxygenDeliveryMethod,
        public ?float $oxygenFlowRate,
        public ?float $bloodGlucose,
        public string $bloodGlucoseUnit,
        public ?int $painScore,
        public ?float $heightCm,
        public ?float $weightKg,
        public ?float $headCircumferenceCm,
        public ?float $chestCircumferenceCm,
        public ?float $muacCm,
        public ?string $capillaryRefill,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   temperature?: int|float|string|null,
         *   temperature_unit: string,
         *   pulse_rate?: int|null,
         *   respiratory_rate?: int|null,
         *   systolic_bp?: int|null,
         *   diastolic_bp?: int|null,
         *   oxygen_saturation?: int|float|string|null,
         *   on_supplemental_oxygen?: bool,
         *   oxygen_delivery_method?: string|null,
         *   oxygen_flow_rate?: int|float|string|null,
         *   blood_glucose?: int|float|string|null,
         *   blood_glucose_unit: string,
         *   pain_score?: int|null,
         *   height_cm?: int|float|string|null,
         *   weight_kg?: int|float|string|null,
         *   head_circumference_cm?: int|float|string|null,
         *   chest_circumference_cm?: int|float|string|null,
         *   muac_cm?: int|float|string|null,
         *   capillary_refill?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            temperature: self::nullableFloat($validated['temperature'] ?? null),
            temperatureUnit: $validated['temperature_unit'],
            pulseRate: self::nullableInt($validated['pulse_rate'] ?? null),
            respiratoryRate: self::nullableInt($validated['respiratory_rate'] ?? null),
            systolicBp: self::nullableInt($validated['systolic_bp'] ?? null),
            diastolicBp: self::nullableInt($validated['diastolic_bp'] ?? null),
            oxygenSaturation: self::nullableFloat($validated['oxygen_saturation'] ?? null),
            onSupplementalOxygen: $validated['on_supplemental_oxygen'] ?? false,
            oxygenDeliveryMethod: self::nullableString($validated['oxygen_delivery_method'] ?? null),
            oxygenFlowRate: self::nullableFloat($validated['oxygen_flow_rate'] ?? null),
            bloodGlucose: self::nullableFloat($validated['blood_glucose'] ?? null),
            bloodGlucoseUnit: $validated['blood_glucose_unit'],
            painScore: self::nullableInt($validated['pain_score'] ?? null),
            heightCm: self::nullableFloat($validated['height_cm'] ?? null),
            weightKg: self::nullableFloat($validated['weight_kg'] ?? null),
            headCircumferenceCm: self::nullableFloat($validated['head_circumference_cm'] ?? null),
            chestCircumferenceCm: self::nullableFloat($validated['chest_circumference_cm'] ?? null),
            muacCm: self::nullableFloat($validated['muac_cm'] ?? null),
            capillaryRefill: self::nullableString($validated['capillary_refill'] ?? null),
        );
    }

    public function meanArterialPressure(): ?int
    {
        if ($this->systolicBp === null || $this->diastolicBp === null) {
            return null;
        }

        return (int) round(($this->systolicBp + (2 * $this->diastolicBp)) / 3);
    }

    public function bodyMassIndex(): ?float
    {
        if ($this->heightCm === null || $this->weightKg === null) {
            return null;
        }

        $heightMeters = $this->heightCm / 100;

        if ($heightMeters <= 0.0) {
            return null;
        }

        return round($this->weightKg / ($heightMeters * $heightMeters), 2);
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
