<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GeneralStatus;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use Illuminate\Database\Seeder;

final class InsurancePackageSeeder extends Seeder
{
    public function run(): void
    {
        $companyPackages = [
            'Jubilee Insurance' => [
                'Jubilee Basic OPD',
                'Jubilee Family Plus',
                'Jubilee Executive Plan',
            ],
            'AAR Health Services' => [
                'AAR Silver',
                'AAR Gold',
                'AAR Corporate',
            ],
            'Cigna International' => [
                'Cigna Local Cover',
                'Cigna International Plus',
            ],
            'NHIF' => [
                'NHIF Standard',
                'NHIF Premium',
            ],
        ];

        $companies = InsuranceCompany::query()->select('id', 'tenant_id', 'name')->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {
            $packages = $companyPackages[$company->name] ?? ['Standard Insurance Package'];

            foreach ($packages as $packageName) {
                InsurancePackage::query()->updateOrCreate(
                    [
                        'tenant_id' => $company->tenant_id,
                        'insurance_company_id' => $company->id,
                        'name' => $packageName,
                    ],
                    [
                        'status' => GeneralStatus::ACTIVE,
                    ]
                );
            }
        }
    }
}
