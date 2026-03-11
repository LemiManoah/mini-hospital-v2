<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GeneralStatus;
use App\Models\InsuranceCompany;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class InsuranceCompanySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->select('id')->get();

        if ($tenants->isEmpty()) {
            return;
        }

        $companies = [
            [
                'name' => 'Jubilee Insurance',
                'email' => 'claims@jubileeinsurance.com',
                'main_contact' => '+256700100100',
                'other_contact' => '+256700100101',
                'address' => 'Kampala, Uganda',
            ],
            [
                'name' => 'AAR Health Services',
                'email' => 'provider@aar-insurance.com',
                'main_contact' => '+256700200200',
                'other_contact' => '+256700200201',
                'address' => 'Kampala, Uganda',
            ],
            [
                'name' => 'Cigna International',
                'email' => 'africa.providers@cigna.com',
                'main_contact' => '+256700300300',
                'other_contact' => '+256700300301',
                'address' => 'Regional Office',
            ],
            [
                'name' => 'NHIF',
                'email' => 'providers@nhif.go.ug',
                'main_contact' => '+256700400400',
                'other_contact' => '+256700400401',
                'address' => 'Kampala, Uganda',
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($companies as $company) {
                InsuranceCompany::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $company['name'],
                    ],
                    [
                        ...$company,
                        'status' => GeneralStatus::ACTIVE,
                    ]
                );
            }
        }
    }
}
