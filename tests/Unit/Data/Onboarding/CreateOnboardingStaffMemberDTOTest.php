<?php

declare(strict_types=1);

use App\Data\Onboarding\CreateOnboardingStaffMemberDTO;
use Illuminate\Foundation\Http\FormRequest;

function createOnboardingStaffRequest(array $validated): FormRequest
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

it('builds onboarding staff dto from validated input', function (): void {
    $dto = CreateOnboardingStaffMemberDTO::fromRequest(createOnboardingStaffRequest([
        'first_name' => 'Asha',
        'last_name' => 'Nurse',
        'middle_name' => '',
        'email' => 'asha@example.com',
        'phone' => '  +256700000002  ',
        'department_ids' => ['dept-1'],
        'staff_position_id' => 'position-1',
        'type' => 'nursing',
        'license_number' => '',
        'specialty' => '  Pediatrics  ',
        'hire_date' => '2026-04-22',
        'is_active' => true,
    ]));

    expect($dto->firstName)->toBe('Asha')
        ->and($dto->middleName)->toBeNull()
        ->and($dto->phone)->toBe('+256700000002')
        ->and($dto->licenseNumber)->toBeNull()
        ->and($dto->specialty)->toBe('Pediatrics')
        ->and($dto->departmentIds)->toBe(['dept-1'])
        ->and($dto->isActive)->toBeTrue();
});
