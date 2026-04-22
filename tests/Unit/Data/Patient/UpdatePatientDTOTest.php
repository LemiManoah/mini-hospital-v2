<?php

declare(strict_types=1);

use App\Data\Patient\UpdatePatientDTO;
use Illuminate\Foundation\Http\FormRequest;

function updatePatientRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
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
}

it('builds an update patient dto from validated input', function (): void {
    $dto = UpdatePatientDTO::fromRequest(updatePatientRequest([
        'first_name' => 'Calvin',
        'last_name' => 'Rush',
        'age_input_mode' => 'dob',
        'date_of_birth' => '1985-08-31',
        'gender' => 'male',
        'email' => '  patient@example.com  ',
        'phone_number' => '+256700000010',
        'next_of_kin_name' => '',
    ]));

    expect($dto->dateOfBirth)->toBe('1985-08-31')
        ->and($dto->email)->toBe('patient@example.com')
        ->and($dto->nextOfKinName)->toBeNull();
});
