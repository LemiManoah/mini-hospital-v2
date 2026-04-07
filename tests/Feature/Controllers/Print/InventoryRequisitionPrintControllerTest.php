<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryLocationType;
use App\Enums\InventoryRequisitionStatus;
use App\Enums\Priority;
use App\Enums\InventoryItemType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createInventoryRequisitionPrintContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Req Print Country '.$sequence,
        'country_code' => 'RP'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Req Print Package '.$sequence,
        'users' => 10 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Req Print Tenant '.$sequence,
        'domain' => 'req-print-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'RP'.$sequence,
        'name' => 'Req Print Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Req Print Branch '.$sequence,
        'branch_code' => 'RPB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'req.print.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $sourceLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Main Store '.$sequence,
        'location_code' => 'RPS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $destinationLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Pharmacy '.$sequence,
        'location_code' => 'RPP'.$sequence,
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Req Print Item '.$sequence,
        'item_type' => InventoryItemType::CONSUMABLE,
        'default_purchase_price' => 25,
        'is_active' => true,
    ]);

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-PRINT-'.$sequence,
        'status' => InventoryRequisitionStatus::Submitted,
        'priority' => Priority::URGENT,
        'requisition_date' => now()->toDateString(),
        'notes' => 'Need urgent stock.',
        'submitted_by' => $user->id,
        'submitted_at' => now(),
    ]);

    $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_quantity' => 5,
        'approved_quantity' => 0,
        'issued_quantity' => 0,
        'notes' => 'Top-up shelves.',
    ]);

    $sequence++;

    return [$branch, $user, $requisition];
}

it('streams a pdf for a requester workspace requisition', function (): void {
    [$branch, $user, $requisition] = createInventoryRequisitionPrintContext();

    $user->assignRole('pharmacist');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.requisitions.print', $requisition));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('prevents pharmacists from printing the main store queue view of a requisition', function (): void {
    [$branch, $user, $requisition] = createInventoryRequisitionPrintContext();

    $user->assignRole('pharmacist');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory-requisitions.print', $requisition))
        ->assertForbidden();
});
