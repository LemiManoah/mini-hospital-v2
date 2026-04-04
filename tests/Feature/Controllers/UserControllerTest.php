<?php

declare(strict_types=1);

use App\Models\FacilityBranch;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createUserManagementContext(): array
{
    $tenantContext = seedTenantContext();
    seedFacilityBranchRecord('branch-main', $tenantContext['tenant_id'], $tenantContext['currency_id']);
    seedFacilityBranchRecord('branch-other', $tenantContext['tenant_id'], $tenantContext['currency_id']);

    $tenant = Tenant::query()->findOrFail($tenantContext['tenant_id']);
    $branch = FacilityBranch::query()->findOrFail('branch-main');
    $otherBranch = FacilityBranch::query()->findOrFail('branch-other');

    $admin = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);

    return [$tenant, $branch, $otherBranch, $admin];
}

function createBranchStaff(FacilityBranch $branch, array $overrides = []): Staff
{
    $staff = Staff::factory()->create([
        'tenant_id' => $branch->tenant_id,
        ...$overrides,
    ]);

    $staff->branches()->attach($branch->id, ['is_primary_location' => true]);

    return $staff;
}

it('renders the managed user create page with active branch staff who do not already have accounts', function (): void {
    [, $branch, $otherBranch, $admin] = createUserManagementContext();

    $availableStaff = createBranchStaff($branch, [
        'first_name' => 'Alice',
        'last_name' => 'Nurse',
        'email' => 'alice.nurse@example.com',
    ]);

    $staffWithAccount = createBranchStaff($branch, [
        'first_name' => 'Bob',
        'last_name' => 'Doctor',
        'email' => 'bob.doctor@example.com',
    ]);

    User::factory()->create([
        'tenant_id' => $branch->tenant_id,
        'staff_id' => $staffWithAccount->id,
        'email' => 'existing.account@example.com',
    ]);

    createBranchStaff($otherBranch, [
        'first_name' => 'Charlie',
        'last_name' => 'Lab',
        'email' => 'charlie.lab@example.com',
    ]);

    $role = Role::query()->create([
        'name' => 'Branch Admin',
        'guard_name' => 'web',
    ]);

    $admin->givePermissionTo('users.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($admin)
        ->get(route('users.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('user/create')
            ->has('staff', 1)
            ->where('staff.0.id', $availableStaff->id)
            ->where('staff.0.first_name', 'Alice')
            ->has('roles')
            ->where('roles', fn ($roles): bool => collect($roles)->contains(
                fn (array $item): bool => $item['id'] === $role->id && $item['name'] === 'Branch Admin'
            )));
});

it('creates a managed user for active branch staff and assigns roles', function (): void {
    [, $branch, , $admin] = createUserManagementContext();

    $staff = createBranchStaff($branch, [
        'first_name' => 'Grace',
        'last_name' => 'Admin',
        'email' => 'grace.admin@example.com',
    ]);

    $role = Role::query()->create([
        'name' => 'Registrar',
        'guard_name' => 'web',
    ]);

    $admin->givePermissionTo('users.create');

    Event::fake([Registered::class]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), [
            'staff_id' => $staff->id,
            'email' => 'grace.user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$role->id],
        ]);

    $response->assertRedirectToRoute('users.index')
        ->assertSessionHas('success', 'User created successfully.');

    $user = User::query()
        ->where('email', 'grace.user@example.com')
        ->first();

    expect($user)->not->toBeNull()
        ->and($user->staff_id)->toBe($staff->id)
        ->and($user->tenant_id)->toBe($admin->tenant_id)
        ->and(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->roles->pluck('name')->all())->toBe(['Registrar']);

    Event::assertDispatched(Registered::class);
});

it('validates required managed user creation fields', function (): void {
    [, $branch, , $admin] = createUserManagementContext();

    $admin->givePermissionTo('users.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), []);

    $response->assertRedirectToRoute('users.create')
        ->assertSessionHasErrors(['staff_id', 'email', 'password']);
});

it('validates managed user email uniqueness and password confirmation', function (): void {
    [, $branch, , $admin] = createUserManagementContext();

    $existingStaff = createBranchStaff($branch, ['email' => 'existing.staff@example.com']);
    User::factory()->create([
        'tenant_id' => $branch->tenant_id,
        'staff_id' => $existingStaff->id,
        'email' => 'duplicate@example.com',
    ]);

    $staff = createBranchStaff($branch, ['email' => 'fresh.staff@example.com']);

    $admin->givePermissionTo('users.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), [
            'staff_id' => $staff->id,
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

    $response->assertRedirectToRoute('users.create')
        ->assertSessionHasErrors(['email', 'password']);
});

it('deletes the current authenticated user account', function (): void {
    [, , , $admin] = createUserManagementContext();

    $response = $this->actingAs($admin)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy-account'), [
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('login')
        ->assertSessionHas('success', 'Account deleted successfully.');

    expect($admin->fresh())->toBeNull();
});

it('requires the current password to delete the authenticated user account', function (): void {
    [, , , $admin] = createUserManagementContext();

    $response = $this->actingAs($admin)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy-account'), [
            'password' => 'wrong-password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($admin->fresh())->not->toBeNull();
});

it('deletes a managed user in the active branch after confirming the current password', function (): void {
    [, $branch, , $admin] = createUserManagementContext();

    $staff = createBranchStaff($branch, [
        'first_name' => 'Diana',
        'last_name' => 'Clerk',
        'email' => 'diana.clerk@example.com',
    ]);

    $managedUser = User::factory()->create([
        'tenant_id' => $branch->tenant_id,
        'staff_id' => $staff->id,
        'email' => 'managed.user@example.com',
    ]);

    $admin->givePermissionTo('users.delete');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($admin)
        ->delete(route('users.destroy', $managedUser), [
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('users.index')
        ->assertSessionHas('success', 'User deleted successfully.');

    expect($managedUser->fresh())->toBeNull();
});
