<?php

declare(strict_types=1);

use App\Actions\UpdateInsurancePackage;
use App\Data\Patient\UpdateInsurancePackageDTO;
use App\Enums\GeneralStatus;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates an insurance package from a typed dto', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $company = InsuranceCompany::query()->create([
        'name' => 'AAR',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $package = InsurancePackage::query()->create([
        'insurance_company_id' => $company->id,
        'name' => 'Starter Cover',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $updated = resolve(UpdateInsurancePackage::class)->handle(
        $package,
        new UpdateInsurancePackageDTO(
            insuranceCompanyId: $company->id,
            name: 'Premium Cover',
            status: GeneralStatus::INACTIVE->value,
        ),
    );

    expect($updated)->toBeInstanceOf(InsurancePackage::class)
        ->and($updated->name)->toBe('Premium Cover')
        ->and($updated->status)->toBe(GeneralStatus::INACTIVE);
});
