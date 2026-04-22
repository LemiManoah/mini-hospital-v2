<?php

declare(strict_types=1);

use App\Data\Patient\CreatePatientRegistrationDTO;
use Illuminate\Foundation\Http\FormRequest;

function createPatientRegistrationRequest(array $validated): FormRequest
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

it('builds a patient registration dto from validated input', function (): void {
    $dto = CreatePatientRegistrationDTO::fromRequest(createPatientRegistrationRequest([
        'first_name' => 'Calvin',
        'last_name' => 'Rush',
        'middle_name' => '',
        'age_input_mode' => 'age',
        'age' => '12',
        'age_units' => 'year',
        'gender' => 'male',
        'email' => '  patient@example.com  ',
        'phone_number' => '+256700000010',
        'alternative_phone' => '',
        'next_of_kin_name' => '  Sarah  ',
        'next_of_kin_phone' => '  +256700000011  ',
        'next_of_kin_relationship' => 'mother',
        'occupation' => '  Student  ',
        'visit_type' => 'opd_consultation',
        'billing_type' => 'cash',
        'is_emergency' => true,
    ]));

    expect($dto->middleName)->toBeNull()
        ->and($dto->age)->toBe(12)
        ->and($dto->email)->toBe('patient@example.com')
        ->and($dto->alternativePhone)->toBeNull()
        ->and($dto->nextOfKinName)->toBe('Sarah')
        ->and($dto->nextOfKinPhone)->toBe('+256700000011')
        ->and($dto->occupation)->toBe('Student')
        ->and($dto->isEmergency)->toBeTrue();
});
