<?php

declare(strict_types=1);

use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function createChargeMasterContext(): array
{
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $chargeMasterId = (string) Str::uuid();

    DB::table('charge_masters')->insert([
        'id' => $chargeMasterId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'item_code' => 'LAB-CBC',
        'description' => 'Complete Blood Count',
        'billable_type' => 'test',
        'billable_id' => null,
        'unit_price' => 25000,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$tenant, $branch, $user, $chargeMasterId];
}

it('shows the charge master registry for the active branch', function (): void {
    [, $branch, $user] = createChargeMasterContext();
    $user->givePermissionTo('charge_masters.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('charge-masters.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('charge-master/index')
            ->where('chargeMasters.data.0.item_code', 'LAB-CBC')
            ->where('chargeMasters.data.0.description', 'Complete Blood Count')
            ->where('billableTypeOptions.0.value', 'service'));
});

it('versions a charge master price directly', function (): void {
    [, $branch, $user, $chargeMasterId] = createChargeMasterContext();
    $user->givePermissionTo('charge_masters.update');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->put(route('charge-masters.update', $chargeMasterId), [
            'unit_price' => 30000,
            'is_active' => true,
            'effective_from' => '2026-05-14',
            'effective_to' => null,
        ])
        ->assertRedirect(route('charge-masters.index'))
        ->assertSessionHas('success', 'Charge master price updated successfully.');

    $this->assertDatabaseHas('charge_masters', [
        'id' => $chargeMasterId,
        'unit_price' => 25000,
        'is_active' => false,
        'effective_to' => '2026-05-13',
        'updated_by' => $user->id,
    ]);

    $this->assertDatabaseHas('charge_masters', [
        'tenant_id' => $user->tenant_id,
        'item_code' => 'LAB-CBC',
        'unit_price' => 30000,
        'is_active' => true,
        'effective_from' => '2026-05-14',
        'effective_to' => null,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
});

it('does not allow updating another branch charge master row', function (): void {
    [$tenant, $branch, $user] = createChargeMasterContext();
    $otherBranch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $otherChargeMasterId = (string) Str::uuid();
    $user->givePermissionTo('charge_masters.update');

    DB::table('charge_masters')->insert([
        'id' => $otherChargeMasterId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $otherBranch->id,
        'item_code' => 'OTHER',
        'description' => 'Other Branch Charge',
        'billable_type' => 'service',
        'billable_id' => null,
        'unit_price' => 10000,
        'is_active' => true,
        'effective_from' => null,
        'effective_to' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->put(route('charge-masters.update', $otherChargeMasterId), [
            'unit_price' => 99999,
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('charge_masters', [
        'id' => $otherChargeMasterId,
        'unit_price' => 10000,
    ]);
});
