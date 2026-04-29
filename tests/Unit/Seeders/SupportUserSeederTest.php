<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SupportUserSeeder;

it('creates a support user mapped to generated tenant support staff', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = Tenant::factory()->create([
        'domain' => 'alpha-facility',
    ]);

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Main Branch',
        'main_contact' => '+256700000001',
        'is_main_branch' => true,
    ]);

    $department = Department::query()->create([
        'tenant_id' => $tenant->id,
        'department_code' => 'ADM',
        'department_name' => 'Administration',
        'is_clinical' => false,
        'is_active' => true,
    ]);

    StaffPosition::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'System Administrator',
        'is_active' => true,
    ]);

    resolve(SupportUserSeeder::class)->run();

    $supportUser = User::query()
        ->with(['roles', 'staff.branches', 'staff.departments'])
        ->where('email', SupportUserSeeder::SUPPORT_EMAIL)
        ->firstOrFail();

    expect($supportUser->isSupportUser())->toBeTrue()
        ->and($supportUser->tenant_id)->toBe($tenant->id)
        ->and($supportUser->staff_id)->not->toBeNull()
        ->and($supportUser->hasRole('super_admin'))->toBeTrue();

    $supportStaff = Staff::query()->findOrFail($supportUser->staff_id);

    expect($supportStaff->tenant_id)->toBe($tenant->id)
        ->and($supportStaff->email)->toBe('support+alpha-facility@mini-hospital.com')
        ->and($supportStaff->employee_number)->toBe('SUP-ALPHA-FACILITY')
        ->and($supportStaff->phone)->toBe('+256700000001');

    expect($supportStaff->branches->pluck('id')->all())->toContain($branch->id);
    expect($supportStaff->departments->pluck('id')->all())->toContain($department->id);
});

it('prefers qroo medical center as the default support tenant when available', function (): void {
    $this->seed(PermissionSeeder::class);

    $alphaTenant = Tenant::factory()->create([
        'domain' => 'alpha-facility',
    ]);

    $qrooTenant = Tenant::factory()->create([
        'domain' => 'qroo',
        'name' => 'Qroo Medical Center',
    ]);

    foreach ([$alphaTenant, $qrooTenant] as $tenant) {
        $branch = FacilityBranch::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'main_contact' => '+256700000001',
            'is_main_branch' => true,
        ]);

        $department = Department::query()->create([
            'tenant_id' => $tenant->id,
            'department_code' => sprintf('ADM-%s', $tenant->domain),
            'department_name' => 'Administration',
            'is_clinical' => false,
            'is_active' => true,
        ]);

        StaffPosition::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'System Administrator',
            'is_active' => true,
        ]);

        expect($branch->tenant_id)->toBe($tenant->id);
        expect($department->tenant_id)->toBe($tenant->id);
    }

    resolve(SupportUserSeeder::class)->run();

    $supportUser = User::query()
        ->where('email', SupportUserSeeder::SUPPORT_EMAIL)
        ->firstOrFail();

    expect($supportUser->tenant_id)->toBe($qrooTenant->id);
});
