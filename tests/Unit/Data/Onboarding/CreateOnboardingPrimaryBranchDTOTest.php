<?php

declare(strict_types=1);

use App\Data\Onboarding\CreateOnboardingPrimaryBranchDTO;
use Illuminate\Foundation\Http\FormRequest;

function createOnboardingPrimaryBranchRequest(array $validated): FormRequest
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

it('builds an onboarding primary branch dto from validated input', function (): void {
    $dto = CreateOnboardingPrimaryBranchDTO::fromRequest(createOnboardingPrimaryBranchRequest([
        'name' => '  Main Hospital  ',
        'branch_code' => ' MH-01 ',
        'email' => '  admin@example.com  ',
        'main_contact' => '  +256700000001  ',
        'other_contact' => '',
        'currency_id' => 'currency-1',
        'address_id' => 'address-1',
        'country_id' => null,
        'has_store' => true,
    ]));

    expect($dto->name)->toBe('  Main Hospital  ')
        ->and($dto->branchCode)->toBe(' MH-01 ')
        ->and($dto->email)->toBe('admin@example.com')
        ->and($dto->mainContact)->toBe('+256700000001')
        ->and($dto->otherContact)->toBeNull()
        ->and($dto->hasStore)->toBeTrue();
});
