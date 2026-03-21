<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void
{
    // ..
}

/**
 * @return array{tenant_id: string, subscription_package_id: string, currency_id: string}
 */
function seedTenantContext(?string $tenantId = null): array
{
    $tenantId ??= (string) Str::uuid();
    $subscriptionPackageId = (string) Str::uuid();
    $currencyId = (string) Str::uuid();

    DB::table('subscription_packages')->insert([
        'id' => $subscriptionPackageId,
        'name' => sprintf('Test Package %s', Str::lower(Str::random(8))),
        'users' => random_int(10, 5000),
        'price' => 0,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('currencies')->insert([
        'id' => $currencyId,
        'code' => sprintf('T%s', Str::upper(Str::random(2))),
        'name' => sprintf('Test Currency %s', Str::upper(Str::random(4))),
        'symbol' => 'T$',
        'modifiable' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tenants')->insert([
        'id' => $tenantId,
        'name' => sprintf('Test Tenant %s', Str::upper(Str::random(8))),
        'domain' => sprintf('tenant-%s', Str::lower(Str::random(10))),
        'has_branches' => true,
        'subscription_package_id' => $subscriptionPackageId,
        'status' => 'active',
        'facility_level' => 'hospital',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'tenant_id' => $tenantId,
        'subscription_package_id' => $subscriptionPackageId,
        'currency_id' => $currencyId,
    ];
}

function seedPatientRecord(string $patientId, string $tenantId): void
{
    DB::table('patients')->insert([
        'id' => $patientId,
        'tenant_id' => $tenantId,
        'patient_number' => sprintf('PAT-%s', Str::upper(Str::random(8))),
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'gender' => 'male',
        'phone_number' => '+256700000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function seedFacilityBranchRecord(string $branchId, string $tenantId, ?string $currencyId = null): string
{
    $currencyId ??= (string) Str::uuid();

    if (! DB::table('currencies')->where('id', $currencyId)->exists()) {
        DB::table('currencies')->insert([
            'id' => $currencyId,
            'code' => sprintf('B%s', Str::upper(Str::random(2))),
            'name' => sprintf('Branch Currency %s', Str::upper(Str::random(4))),
            'symbol' => 'B$',
            'modifiable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    DB::table('facility_branches')->insert([
        'id' => $branchId,
        'name' => 'Main Branch',
        'branch_code' => sprintf('BR-%s', Str::upper(Str::random(6))),
        'tenant_id' => $tenantId,
        'currency_id' => $currencyId,
        'status' => 'active',
        'is_main_branch' => true,
        'has_store' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $currencyId;
}

/**
 * @return array{insurance_company_id: string, insurance_package_id: string}
 */
function seedInsuranceCoverage(string $tenantId, string $insuranceCompanyId, string $insurancePackageId): array
{
    DB::table('insurance_companies')->insert([
        'id' => $insuranceCompanyId,
        'tenant_id' => $tenantId,
        'name' => sprintf('Test Insurance %s', Str::upper(Str::random(6))),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_packages')->insert([
        'id' => $insurancePackageId,
        'tenant_id' => $tenantId,
        'insurance_company_id' => $insuranceCompanyId,
        'name' => sprintf('Test Cover %s', Str::upper(Str::random(6))),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
    ];
}
