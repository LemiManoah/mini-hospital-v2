<?php

declare(strict_types=1);

use App\Actions\CreateInsurancePackage;
use App\Data\Patient\CreateInsurancePackageDTO;
use App\Enums\GeneralStatus;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('creates an insurance package from a typed dto', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $company = InsuranceCompany::query()->create([
        'name' => 'AAR',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $package = resolve(CreateInsurancePackage::class)->handle(
        new CreateInsurancePackageDTO(
            insuranceCompanyId: $company->id,
            name: 'Standard Cover',
            status: GeneralStatus::ACTIVE->value,
        ),
    );

    expect($package)->toBeInstanceOf(InsurancePackage::class)
        ->and($package->insurance_company_id)->toBe($company->id)
        ->and($package->name)->toBe('Standard Cover')
        ->and($package->status)->toBe(GeneralStatus::ACTIVE);
});
