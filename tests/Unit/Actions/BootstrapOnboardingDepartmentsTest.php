<?php

declare(strict_types=1);

use App\Actions\BootstrapOnboardingDepartments;
use App\Data\Onboarding\CreateOnboardingDepartmentDTO;
use App\Data\Onboarding\CreateOnboardingDepartmentsDTO;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('bootstraps onboarding departments using a typed dto', function (): void {
    $tenantId = (string) Str::uuid();
    $userId = (string) Str::uuid();

    seedTenantContext($tenantId);

    DB::table('users')->insert([
        'id' => $userId,
        'tenant_id' => $tenantId,
        'email' => 'dept-owner@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::query()->findOrFail($tenantId);
    $user = User::query()->findOrFail($userId);

    $dto = new CreateOnboardingDepartmentsDTO([
        new CreateOnboardingDepartmentDTO(
            name: 'Outpatient',
            location: 'Ground Floor',
            isClinical: true,
        ),
        new CreateOnboardingDepartmentDTO(
            name: 'Finance',
            location: null,
            isClinical: false,
        ),
    ]);

    resolve(BootstrapOnboardingDepartments::class)->handle($tenant, $user, $dto);

    expect(DB::table('departments')->where('tenant_id', $tenantId)->count())->toBe(2)
        ->and($tenant->fresh()->onboarding_current_step)->toBe('staff');
});
