<?php

declare(strict_types=1);

use App\Enums\StaffType;
use App\Enums\UnitType;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('updates a staff member through the typed route-bound request', function (): void {
    $tenantContext = seedTenantContext();
    $branchId = (string) Str::uuid();
    seedFacilityBranchRecord($branchId, $tenantContext['tenant_id'], $tenantContext['currency_id']);

    $tenant = Tenant::query()->findOrFail($tenantContext['tenant_id']);
    $branch = FacilityBranch::query()->findOrFail($branchId);

    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'staff-updater@example.com',
    ]);
    $admin->givePermissionTo('staff.update');

    $department = Department::query()->create([
        'tenant_id' => $tenant->id,
        'department_code' => 'DEP-STAFF',
        'department_name' => 'Outpatient',
        'is_clinical' => true,
        'is_active' => true,
    ]);

    $position = StaffPosition::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Clinical Officer',
        'description' => null,
        'is_active' => true,
    ]);

    $staff = Staff::factory()->create([
        'tenant_id' => $tenant->id,
        'staff_position_id' => $position->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
        'type' => StaffType::MEDICAL,
    ]);

    $staff->departments()->attach($department->id);
    $staff->branches()->attach($branch->id, ['is_primary_location' => true]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($admin)
        ->from(route('staff.edit', $staff))
        ->put(route('staff.update', $staff), [
            'first_name' => 'Janet',
            'last_name' => 'Doe',
            'middle_name' => 'A',
            'email' => 'janet.doe@example.com',
            'phone' => '+256700000111',
            'department_ids' => [$department->id],
            'staff_position_id' => $position->id,
            'type' => StaffType::MEDICAL->value,
            'license_number' => 'LIC-100',
            'specialty' => 'Family Medicine',
            'hire_date' => now()->toDateString(),
            'is_active' => true,
            'branch_ids' => [$branch->id],
            'primary_branch_id' => $branch->id,
        ]);

    $response->assertRedirectToRoute('staff.index')
        ->assertSessionHas('success', 'Staff member updated successfully.');

    expect($staff->fresh())
        ->first_name->toBe('Janet')
        ->email->toBe('janet.doe@example.com');
});

it('updates a unit through the typed route-bound request', function (): void {
    $tenantContext = seedTenantContext();
    $tenant = Tenant::query()->findOrFail($tenantContext['tenant_id']);

    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'unit-updater@example.com',
    ]);
    $admin->givePermissionTo('units.update');

    $unit = Unit::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Box',
        'symbol' => 'bx',
        'type' => UnitType::COUNT,
    ]);

    $response = $this->actingAs($admin)
        ->from(route('units.edit', $unit))
        ->put(route('units.update', $unit), [
            'name' => 'Carton',
            'symbol' => 'ctn',
            'description' => 'Updated carton unit',
            'type' => UnitType::COUNT->value,
        ]);

    $response->assertRedirectToRoute('units.index')
        ->assertSessionHas('success', 'Unit updated successfully.');

    expect($unit->fresh())
        ->name->toBe('Carton')
        ->symbol->toBe('ctn');
});
