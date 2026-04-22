<?php

declare(strict_types=1);

use App\Actions\BootstrapOnboardingStaffMember;
use App\Data\Onboarding\CreateOnboardingStaffMemberDTO;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('bootstraps onboarding staff using a typed dto', function (): void {
    $tenantId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $departmentId = (string) Str::uuid();
    $positionId = (string) Str::uuid();

    seedTenantContext($tenantId);
    seedFacilityBranchRecord($branchId, $tenantId);

    DB::table('users')->insert([
        'id' => $userId,
        'tenant_id' => $tenantId,
        'email' => 'staff-owner@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('departments')->insert([
        'id' => $departmentId,
        'tenant_id' => $tenantId,
        'department_code' => 'OUT01',
        'department_name' => 'Outpatient',
        'location' => 'Ground Floor',
        'is_clinical' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('staff_positions')->insert([
        'id' => $positionId,
        'tenant_id' => $tenantId,
        'name' => 'Nurse',
        'description' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::query()->findOrFail($tenantId);
    $user = User::query()->findOrFail($userId);

    $dto = new CreateOnboardingStaffMemberDTO(
        firstName: 'Asha',
        lastName: 'Nurse',
        middleName: null,
        email: 'asha.nurse@example.com',
        phone: '+256700000003',
        departmentIds: [$departmentId],
        staffPositionId: $positionId,
        type: 'nursing',
        licenseNumber: null,
        specialty: 'Pediatrics',
        hireDate: now()->toDateString(),
        isActive: true,
    );

    resolve(BootstrapOnboardingStaffMember::class)->handle($tenant, $user, $dto);

    expect(DB::table('staff')->where('tenant_id', $tenantId)->count())->toBe(1)
        ->and($tenant->fresh()->onboarding_current_step)->toBe('complete')
        ->and($tenant->fresh()->onboarding_completed_at)->not->toBeNull();
});
