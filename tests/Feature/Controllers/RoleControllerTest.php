<?php

declare(strict_types=1);

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
