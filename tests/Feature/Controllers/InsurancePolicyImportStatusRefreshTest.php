<?php

declare(strict_types=1);

use App\Enums\DataImportStatus;
use App\Enums\GeneralStatus;
use App\Enums\InsurancePolicyType;
use App\Models\DataImport;
use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createInsurancePolicyImportRefreshContext(): array
{
    $tenantContext = seedTenantContext();

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'currency_id' => $tenantContext['currency_id'],
        'name' => 'City General Hospital',
        'branch_code' => 'CGH-MAIN',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('insurance_packages.view');

    $insuranceCompanyId = (string) Str::uuid();
    $insurancePackageId = (string) Str::uuid();
    $insurancePolicyId = (string) Str::uuid();

    DB::table('insurance_companies')->insert([
        'id' => $insuranceCompanyId,
        'tenant_id' => $tenantContext['tenant_id'],
        'name' => 'Acme Health',
        'status' => GeneralStatus::ACTIVE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_packages')->insert([
        'id' => $insurancePackageId,
        'tenant_id' => $tenantContext['tenant_id'],
        'insurance_company_id' => $insuranceCompanyId,
        'name' => 'Corporate Cover',
        'status' => GeneralStatus::ACTIVE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_policies')->insert([
        'id' => $insurancePolicyId,
        'tenant_id' => $tenantContext['tenant_id'],
        'facility_branch_id' => $branch->id,
        'insurance_package_id' => $insurancePackageId,
        'name' => 'Pharmacy Policy',
        'policy_type' => InsurancePolicyType::PHARMACY->value,
        'status' => GeneralStatus::ACTIVE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$tenantContext['tenant_id'], $branch, $user, $insurancePackageId, $insurancePolicyId];
}

it('exposes queued policy imports and completed import results for automatic refreshes', function (): void {
    [$tenantId, $branch, $user, $insurancePackageId, $insurancePolicyId] = createInsurancePolicyImportRefreshContext();

    $baseContext = [
        'insurance_package_id' => $insurancePackageId,
        'insurance_policy_id' => $insurancePolicyId,
        'insurance_policy_name' => 'Pharmacy Policy',
        'policy_type' => InsurancePolicyType::PHARMACY->value,
        'item_type' => InsurancePolicyType::PHARMACY->itemType()->value,
        'branch_name' => $branch->name,
    ];

    DataImport::query()->create([
        'tenant_id' => $tenantId,
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'import_type' => 'insurance_policy_items',
        'source_filename' => 'policy-queued.csv',
        'stored_path' => 'imports/insurance-policies/policy-queued.csv',
        'status' => DataImportStatus::Queued,
        'context' => $baseContext,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    DataImport::query()->create([
        'tenant_id' => $tenantId,
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'import_type' => 'insurance_policy_items',
        'source_filename' => 'policy-complete.csv',
        'stored_path' => 'imports/insurance-policies/policy-complete.csv',
        'status' => DataImportStatus::Completed,
        'imported_count' => 5,
        'skipped_count' => 1,
        'error_report' => [
            ['row' => 6, 'name' => 'Missing Drug', 'messages' => ['No matching billable item was found.']],
        ],
        'context' => $baseContext,
        'completed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->get(sprintf('/insurance-packages/%s', $insurancePackageId))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('insurance-package/show')
            ->where('policyImports.0.status', 'completed')
            ->where('policyImports.0.policyId', $insurancePolicyId)
            ->where('policyImports.1.status', 'queued')
            ->where('importResult.imported', 5)
            ->where('importResult.skipped', 1)
            ->where('importResultMode', 'import'),
        );
});
