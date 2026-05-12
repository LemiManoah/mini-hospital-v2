<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\StockMovementType;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Staff;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('shows inventory item stock movement actors using the user name accessor', function (): void {
    $tenantContext = seedTenantContext();

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'currency_id' => $tenantContext['currency_id'],
        'status' => GeneralStatus::ACTIVE,
    ]);

    $staff = Staff::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'staff_id' => $staff->id,
        'is_support' => true,
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('inventory_items.view');

    $item = InventoryItem::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'name' => 'Artemether/Lumefantrine',
        'item_type' => InventoryItemType::DRUG,
    ]);

    $location = InventoryLocation::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'branch_id' => $branch->id,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    DB::table('stock_movements')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantContext['tenant_id'],
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'inventory_item_id' => $item->id,
        'movement_type' => StockMovementType::OpeningBalance->value,
        'quantity' => 12,
        'unit_cost' => 350,
        'occurred_at' => now(),
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->get(route('inventory-items.show', $item))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('inventory/items/show')
            ->where('inventoryItem.stock_movements.0.user.name', 'Ada Lovelace'),
        );
});
