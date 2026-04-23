<?php

declare(strict_types=1);

use App\Data\Patient\UpdateInsurancePackageDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes insurance package input into an update dto', function (): void {
    $request = new class(['insurance_company_id' => 'company-1', 'name' => '  Premium Cover  ', 'status' => 'inactive']) extends FormRequest
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

    $dto = UpdateInsurancePackageDTO::fromRequest($request);

    expect($dto->insuranceCompanyId)->toBe('company-1')
        ->and($dto->name)->toBe('Premium Cover')
        ->and($dto->status)->toBe('inactive')
        ->and($dto->toAttributes())->toBe([
            'insurance_company_id' => 'company-1',
            'name' => 'Premium Cover',
            'status' => 'inactive',
        ]);
});
