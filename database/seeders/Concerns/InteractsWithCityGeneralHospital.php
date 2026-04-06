<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\Address;
use App\Models\Country;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use App\Models\User;

trait InteractsWithCityGeneralHospital
{
    protected function cityGeneralTenant(): ?Tenant
    {
        return Tenant::query()
            ->where('domain', 'citygeneral')
            ->first();
    }

    protected function cityGeneralMainBranch(Tenant $tenant): ?FacilityBranch
    {
        return FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_code', 'CGH-MAIN')
            ->first()
            ?? FacilityBranch::query()
                ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_main_branch')
            ->orderBy('name')
            ->first();
    }

    protected function cityGeneralRegistrar(Tenant $tenant): ?User
    {
        return User::query()
            ->where('email', 'support+citygeneral@mini-hospital.com')
            ->first()
            ?? User::query()
                ->where('tenant_id', $tenant->id)->oldest()
                ->first();
    }

    protected function cityGeneralSupportStaff(Tenant $tenant): ?Staff
    {
        return Staff::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', 'support+citygeneral@mini-hospital.com')
            ->first()
            ?? Staff::query()
                ->where('tenant_id', $tenant->id)->oldest()
                ->first();
    }

    protected function ugandaCountry(): ?Country
    {
        return Country::query()
            ->where('country_code', 'UG')
            ->first();
    }

    /**
     * @param  array{city: string, district?: string|null, state?: string|null}  $attributes
     */
    protected function upsertAddress(array $attributes, ?Country $country): Address
    {
        return Address::query()->updateOrCreate(
            [
                'city' => $attributes['city'],
                'district' => $attributes['district'] ?? null,
                'country_id' => $country?->id,
            ],
            [
                'state' => $attributes['state'] ?? null,
            ],
        );
    }

    protected function findDepartment(Tenant $tenant, string $name): ?Department
    {
        return Department::query()
            ->where('tenant_id', $tenant->id)
            ->where('department_name', $name)
            ->first();
    }

    /**
     * @param  list<string>  $names
     */
    protected function findPosition(Tenant $tenant, array $names): ?StaffPosition
    {
        foreach ($names as $name) {
            $position = StaffPosition::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $name)
                ->first();

            if ($position instanceof StaffPosition) {
                return $position;
            }
        }

        return null;
    }
}
