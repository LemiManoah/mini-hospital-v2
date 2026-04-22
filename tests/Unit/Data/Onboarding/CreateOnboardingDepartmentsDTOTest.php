<?php

declare(strict_types=1);

use App\Data\Onboarding\CreateOnboardingDepartmentsDTO;
use Illuminate\Foundation\Http\FormRequest;

function createOnboardingDepartmentsRequest(array $validated): FormRequest
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

it('builds onboarding departments dto from validated input', function (): void {
    $dto = CreateOnboardingDepartmentsDTO::fromRequest(createOnboardingDepartmentsRequest([
        'departments' => [[
            'name' => '  Outpatient  ',
            'location' => '  Ground Floor  ',
            'is_clinical' => true,
        ]],
    ]));

    expect($dto->departments)->toHaveCount(1)
        ->and($dto->departments[0]->name)->toBe('Outpatient')
        ->and($dto->departments[0]->location)->toBe('Ground Floor')
        ->and($dto->departments[0]->isClinical)->toBeTrue();
});
