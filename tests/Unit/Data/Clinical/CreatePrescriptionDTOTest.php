<?php

declare(strict_types=1);

use App\Data\Clinical\CreatePrescriptionDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes nullable prescription strings while preserving typed items', function (): void {
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
    {
        public function __construct(private array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $data = CreatePrescriptionDTO::fromRequest($request([
        'primary_diagnosis' => '  Malaria  ',
        'pharmacy_notes' => '   ',
        'is_discharge_medication' => true,
        'is_long_term' => false,
        'items' => [[
            'inventory_item_id' => 'drug-1',
            'dosage' => '1 tablet',
            'frequency' => 'TDS',
            'route' => 'oral',
            'duration_days' => 5,
            'quantity' => 15,
            'instructions' => '  After meals  ',
            'is_prn' => true,
            'prn_reason' => '  Fever  ',
            'is_external_pharmacy' => false,
        ]],
    ]));

    expect($data->primaryDiagnosis)->toBe('Malaria')
        ->and($data->pharmacyNotes)->toBeNull()
        ->and($data->isDischargeMedication)->toBeTrue()
        ->and($data->items)->toHaveCount(1)
        ->and($data->items[0]->instructions)->toBe('After meals')
        ->and($data->items[0]->prnReason)->toBe('Fever')
        ->and($data->items[0]->isPrn)->toBeTrue();
});
