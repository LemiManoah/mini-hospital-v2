<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

it('provides grouped permissions to the create role page for an authorized user', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create([
        'tenant_id' => null,
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('roles.create');

    $response = $this->actingAs($user)->get(route('roles.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('role/create')
            ->has('permissionGroups.countries', 4)
            ->where('permissionGroups.countries.0.name', 'countries.view')
            ->where('permissionGroups.countries.1.name', 'countries.create')
            ->where('permissionGroups.countries.2.name', 'countries.update')
            ->where('permissionGroups.countries.3.name', 'countries.delete')
            ->has('permissionGroups.roles', 4)
            ->where('permissionGroups.roles.0.name', 'roles.view')
            ->where('permissionGroups.roles.1.name', 'roles.create'));
});

it('creates updates and deletes roles while recording access audit events', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create([
        'tenant_id' => null,
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo(['roles.create', 'roles.update', 'roles.delete', 'roles.view']);

    $this->actingAs($user)
        ->post(route('roles.store'), [
            'name' => 'Audit Role',
            'permissions' => ['roles.view', 'users.view'],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::query()->where('name', 'Audit Role')->firstOrFail();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'access',
        'event' => 'access.role.created',
        'subject_type' => Role::class,
        'subject_id' => $role->id,
    ]);

    $this->actingAs($user)
        ->put(route('roles.update', $role), [
            'name' => 'Audit Role Updated',
            'permissions' => ['roles.view'],
        ])
        ->assertRedirect(route('roles.index'));

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'access',
        'event' => 'access.role.updated',
        'subject_type' => Role::class,
        'subject_id' => $role->id,
    ]);
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'access',
        'event' => 'access.role.permissions_changed',
        'subject_type' => Role::class,
        'subject_id' => $role->id,
    ]);

    $this->actingAs($user)
        ->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'));

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'access',
        'event' => 'access.role.deleted',
        'subject_type' => Role::class,
        'subject_id' => $role->id,
    ]);
});
