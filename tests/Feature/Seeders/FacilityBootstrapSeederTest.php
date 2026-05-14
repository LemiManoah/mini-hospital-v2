<?php

declare(strict_types=1);

use App\Models\ChargeMaster;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\AddressSeeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ConsultationFacilityServiceSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\FacilitySeeder;
use Database\Seeders\FacilityUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\QrooMedicalCenterSeeder;
use Database\Seeders\StaffPositionSeeder;
use Database\Seeders\SubscriptionPackageSeeder;
use Database\Seeders\SupportUserSeeder;

it('seeds qroo medical center as the default uganda facility with a single main branch', function (): void {
    $this->seed([
        CountrySeeder::class,
        CurrencySeeder::class,
        SubscriptionPackageSeeder::class,
        AddressSeeder::class,
        FacilitySeeder::class,
        PermissionSeeder::class,
        AdminUserSeeder::class,
        DepartmentSeeder::class,
        StaffPositionSeeder::class,
        SupportUserSeeder::class,
    ]);

    $tenant = Tenant::query()->where('domain', 'qroo')->firstOrFail();

    expect($tenant->name)->toBe('Qroo Medical Center')
        ->and((bool) $tenant->has_branches)->toBeFalse();

    $branches = FacilityBranch::query()
        ->where('tenant_id', $tenant->id)
        ->get();

    expect($branches)->toHaveCount(1)
        ->and($branches->first()?->branch_code)->toBe('QMC-MAIN')
        ->and($branches->first()?->name)->toBe('Main Branch')
        ->and($branches->first()?->is_main_branch)->toBeTrue();

    $adminUser = User::query()->where('email', 'admin@qroomedical.ug')->firstOrFail();
    $supportUser = User::query()->where('email', SupportUserSeeder::SUPPORT_EMAIL)->firstOrFail();

    expect($adminUser->tenant_id)->toBe($tenant->id)
        ->and($adminUser->hasRole('admin'))->toBeTrue()
        ->and($supportUser->tenant_id)->toBe($tenant->id);
});

it('seeds default consultation facility services and sample role users for seeded facilities', function (): void {
    $this->seed([
        CountrySeeder::class,
        CurrencySeeder::class,
        SubscriptionPackageSeeder::class,
        AddressSeeder::class,
        FacilitySeeder::class,
        PermissionSeeder::class,
        AdminUserSeeder::class,
        DepartmentSeeder::class,
        StaffPositionSeeder::class,
        QrooMedicalCenterSeeder::class,
        SupportUserSeeder::class,
        ConsultationFacilityServiceSeeder::class,
        FacilityUserSeeder::class,
    ]);

    $qroo = Tenant::query()->where('domain', 'qroo')->firstOrFail();

    expect(
        ChargeMaster::query()
            ->where('tenant_id', $qroo->id)
            ->whereIn('item_code', [
                'QROO-CONS-NEW',
                'QROO-CONS-FUP',
                'QROO-CONS-OPD',
                'QROO-CONS-EMR',
                'QROO-CONS-TEL',
                'QROO-CONS-REV',
                'QROO-CONS-GEN',
            ])
            ->count()
    )->toBe(7);

    expect(
        FacilityService::query()
            ->where('tenant_id', $qroo->id)
            ->where('is_consultation', true)
            ->count()
    )->toBeGreaterThanOrEqual(7);

    expect(User::query()->where('email', 'reception@qroomedical.ug')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'cashier@qroomedical.ug')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'accounts@qroomedical.ug')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'dr.grace.namara@qroomedical.ug')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'reception@kigaliheights.rw')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'cashier@kigaliheights.rw')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'doctor@nairoimedical.ke')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'accounts@nairoimedical.ke')->exists())->toBeTrue();
});
