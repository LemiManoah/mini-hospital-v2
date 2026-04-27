<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Tenant;
use Database\Seeders\AddressSeeder;
use Database\Seeders\AllergenSeeder;
use Database\Seeders\CityGeneralHospitalSeeder;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\FacilitySeeder;
use Database\Seeders\InsuranceCompanySeeder;
use Database\Seeders\InsurancePackageSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\StaffPositionSeeder;
use Database\Seeders\SubscriptionPackageSeeder;
use Database\Seeders\SupportUserSeeder;
use Database\Seeders\UnitSeeder;

it('seeds city general hospital report-ready appointments and same-day payments', function (): void {
    $this->seed([
        CountrySeeder::class,
        CurrencySeeder::class,
        SubscriptionPackageSeeder::class,
        AllergenSeeder::class,
        UnitSeeder::class,
        AddressSeeder::class,
        FacilitySeeder::class,
        PermissionSeeder::class,
        DepartmentSeeder::class,
        StaffPositionSeeder::class,
        InsuranceCompanySeeder::class,
        InsurancePackageSeeder::class,
        SupportUserSeeder::class,
        ClinicSeeder::class,
        CityGeneralHospitalSeeder::class,
    ]);

    $tenant = Tenant::query()->where('domain', 'citygeneral')->firstOrFail();

    expect(
        Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('appointment_date', now()->toDateString())
            ->count()
    )->toBeGreaterThanOrEqual(4);

    expect(
        Payment::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('payment_date', now()->toDateString())
            ->count()
    )->toBeGreaterThanOrEqual(3);
});
