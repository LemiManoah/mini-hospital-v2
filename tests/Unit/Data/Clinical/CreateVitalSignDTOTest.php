<?php

declare(strict_types=1);

use App\Data\Clinical\CreateVitalSignDTO;
use Illuminate\Foundation\Http\FormRequest;

function createVitalSignRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
    {
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };
}

it('builds a vital sign dto from validated input and computes derived values', function (): void {
    $dto = CreateVitalSignDTO::fromRequest(createVitalSignRequest([
        'temperature' => '37.2',
        'temperature_unit' => 'celsius',
        'systolic_bp' => 120,
        'diastolic_bp' => 80,
        'oxygen_saturation' => '98',
        'on_supplemental_oxygen' => true,
        'oxygen_delivery_method' => '  nasal cannula  ',
        'blood_glucose' => '108.5',
        'blood_glucose_unit' => 'mg_dl',
        'height_cm' => '180',
        'weight_kg' => '75',
        'capillary_refill' => '  normal  ',
    ]));

    expect($dto->temperature)->toBe(37.2)
        ->and($dto->oxygenSaturation)->toBe(98.0)
        ->and($dto->oxygenDeliveryMethod)->toBe('nasal cannula')
        ->and($dto->bloodGlucose)->toBe(108.5)
        ->and($dto->meanArterialPressure())->toBe(93)
        ->and($dto->bodyMassIndex())->toBe(23.15)
        ->and($dto->capillaryRefill)->toBe('normal');
});
