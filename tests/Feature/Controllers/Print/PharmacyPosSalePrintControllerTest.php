<?php

declare(strict_types=1);

use App\Enums\PharmacyPosCartStatus;
use App\Enums\PharmacyPosSaleStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosPayment;
use App\Models\PharmacyPosSale;
use App\Models\PharmacyPosSaleItem;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createPharmacyPosPrintContext(): array
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
        'first_name' => 'Pharma',
        'last_name' => 'Printer',
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'pharmacy.print@test.com',
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

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-PRINT-001',
        'customer_name' => 'Jane Doe',
        'customer_phone' => '+256700111222',
        'status' => PharmacyPosCartStatus::Converted,
        'converted_at' => now(),
    ]);

    $item = InventoryItem::factory()->drug()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Amoxicillin 500mg',
        'default_selling_price' => 1500,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $sale = PharmacyPosSale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'pharmacy_pos_cart_id' => $cart->id,
        'sale_number' => 'POS-PRINT-001',
        'sale_type' => 'walk_in',
        'gross_amount' => 3000,
        'discount_amount' => 0,
        'paid_amount' => 3000,
        'balance_amount' => 0,
        'change_amount' => 0,
        'customer_name' => 'Jane Doe',
        'customer_phone' => '+256700111222',
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    PharmacyPosSaleItem::query()->create([
        'pharmacy_pos_sale_id' => $sale->id,
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'unit_price' => 1500,
        'discount_amount' => 0,
        'line_total' => 3000,
    ]);

    PharmacyPosPayment::query()->create([
        'pharmacy_pos_sale_id' => $sale->id,
        'amount' => 3000,
        'payment_method' => 'cash',
        'payment_date' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    return [$branch, $user, $sale];
}

it('streams a pdf for a completed pharmacy pos sale', function (): void {
    [$branch, $user, $sale] = createPharmacyPosPrintContext();

    $user->givePermissionTo('pharmacy_pos.view_history');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.sales.print', $sale));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});
