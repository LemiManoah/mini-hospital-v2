<?php

declare(strict_types=1);

use App\Enums\PharmacyPosCartStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createPharmacyPosCartContext(): array
{
    $country = Country::factory()->create();
    $package = SubscriptionPackage::factory()->create();
    $tenant = Tenant::factory()->create([
        'country_id' => $country->id,
        'subscription_package_id' => $package->id,
    ]);
    $currency = Currency::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
    ]);
    $staff = Staff::factory()->create([
        'tenant_id' => $tenant->id,
        'first_name' => 'Counter',
        'last_name' => 'User',
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'pharmacy.counter@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
        'email_verified_at' => now(),
    ]);

    $location = InventoryLocation::factory()->pharmacy()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $item = InventoryItem::factory()->drug()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Cetrizine 10mg',
        'default_selling_price' => 2000,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-VALIDATE-001',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'unit_price' => 2000,
        'discount_amount' => 0,
    ]);

    return [$branch, $user, $cart];
}

it('requires customer details when finalizing a partially paid pos sale', function (): void {
    [$branch, $user, $cart] = createPharmacyPosCartContext();

    $user->givePermissionTo('pharmacy_pos.complete');
    $csrfToken = 'pharmacy-pos-test-token';

    $response = $this->withSession([
        'active_branch_id' => $branch->id,
        '_token' => $csrfToken,
    ])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            '_token' => $csrfToken,
            'paid_amount' => 1000,
            'payment_method' => 'cash',
            'reference_number' => '',
            'notes' => '',
        ]);

    $response->assertSessionHasErrors(['customer_name', 'customer_phone']);
});
