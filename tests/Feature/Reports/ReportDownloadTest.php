<?php

declare(strict_types=1);

use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\StockMovementType;
use App\Enums\UnitType;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationItem;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createReportDownloadContext(): array
{
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $user = User::factory()
        ->withoutTwoFactor()
        ->create([
            'tenant_id' => $tenant->id,
        ]);

    $user->givePermissionTo('reports.view');

    return [$branch, $user];
}

function seedStockLevelReportData(FacilityBranch $branch, User $user): array
{
    $unit = Unit::query()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Pieces',
        'symbol' => 'pcs',
        'type' => UnitType::COUNT,
    ]);

    $location = InventoryLocation::factory()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'type' => InventoryLocationType::MAIN_STORE,
        'name' => 'Main Store',
        'location_code' => 'MAIN',
    ]);

    $item = InventoryItem::factory()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Amoxicillin 500mg',
        'item_type' => InventoryItemType::DRUG,
        'unit_id' => $unit->id,
        'minimum_stock_level' => 10,
        'reorder_level' => 20,
    ]);

    InventoryLocationItem::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'inventory_item_id' => $item->id,
        'minimum_stock_level' => 10,
        'reorder_level' => 20,
        'is_active' => true,
    ]);

    StockMovement::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'inventory_item_id' => $item->id,
        'movement_type' => StockMovementType::OpeningBalance,
        'quantity' => 15,
        'occurred_at' => now(),
        'created_by' => $user->id,
    ]);

    return [$location, $item];
}

it('renders the report generator page', function (): void {
    [$branch, $user] = createReportDownloadContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->where('selectedReport', 'daily-revenue')
            ->has('reports', 4)
            ->has('preview.columns'));
});

it('downloads the daily revenue report pdf', function (): void {
    [$branch, $user] = createReportDownloadContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.daily-revenue.download'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('downloads the appointment schedule report pdf', function (): void {
    [$branch, $user] = createReportDownloadContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.appointment-schedule.download'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('downloads the low stock alert report pdf', function (): void {
    [$branch, $user] = createReportDownloadContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.low-stock.download'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('downloads the stock level report pdf', function (): void {
    [$branch, $user] = createReportDownloadContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.stock-level.download'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('renders the stock level report when inventory items have units', function (): void {
    [$branch, $user] = createReportDownloadContext();

    seedStockLevelReportData($branch, $user);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.stock-level.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/stock-level')
            ->where('report.rows.0.unit', 'pcs')
            ->where('report.rows.0.quantity', 15));
});

it('renders stock level preview inside the report generator', function (): void {
    [$branch, $user] = createReportDownloadContext();
    [$location] = seedStockLevelReportData($branch, $user);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.index', [
            'report' => 'stock-level',
            'location_id' => $location->id,
        ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->where('selectedReport', 'stock-level')
            ->where('filters.location_id', $location->id)
            ->where('preview.rows.0.unit', 'pcs'));
});

it('exports the selected report as csv from the report generator', function (): void {
    [$branch, $user] = createReportDownloadContext();
    [$location] = seedStockLevelReportData($branch, $user);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reports.export-csv', [
            'report' => 'stock-level',
            'location_id' => $location->id,
        ]));

    $response->assertOk();

    expect((string) $response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->streamedContent())->toContain('Item Name')
        ->and($response->streamedContent())->toContain('Amoxicillin 500mg');
});
