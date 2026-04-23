<?php

declare(strict_types=1);

use App\Data\Patient\CreateInsurancePackageDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes insurance package input into a create dto', function (): void {
    $request = new class(['insurance_company_id' => 'company-1', 'name' => '  Standard Cover  ', 'status' => 'active']) extends FormRequest
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

    $dto = CreateInsurancePackageDTO::fromRequest($request);

    expect($dto->insuranceCompanyId)->toBe('company-1')
        ->and($dto->name)->toBe('Standard Cover')
        ->and($dto->status)->toBe('active')
        ->and($dto->toAttributes())->toBe([
            'insurance_company_id' => 'company-1',
            'name' => 'Standard Cover',
            'status' => 'active',
        ]);
});
