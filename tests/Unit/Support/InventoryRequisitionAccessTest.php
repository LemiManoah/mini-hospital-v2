<?php

declare(strict_types=1);

use App\Enums\InventoryLocationType;
use App\Enums\InventoryRequisitionStatus;
use App\Models\FacilityBranch;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\Tenant;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryRequisitionAccess;
use App\Support\InventoryRequisitionWorkflow;
use App\Support\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

it('uses fulfilling location types for main-store requisition index access', function (): void {
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $mainStore = InventoryLocation::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'type' => InventoryLocationType::MAIN_STORE,
    ]);
    InventoryLocation::factory()->pharmacy()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
    ]);

    $access = new InventoryRequisitionAccess(
        new InventoryLocationAccess(),
        new InventoryRequisitionWorkflow(),
    );
    $workspace = inventoryWorkspace('inventory-requisitions.index');

    expect($access->indexLocationIds(null, $workspace, $branch->id))
        ->toBe([$mainStore->id]);
});

it('matches requester workspaces using requisition relationships', function (): void {
    $access = new InventoryRequisitionAccess(
        new InventoryLocationAccess(),
        new InventoryRequisitionWorkflow(),
    );
    $workspace = inventoryWorkspace('pharmacy.requisitions.show');
    $requisition = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Submitted,
    ]);
    $requisition->setRelation('fulfillingLocation', new InventoryLocation([
        'type' => InventoryLocationType::MAIN_STORE,
    ]));
    $requisition->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::PHARMACY,
    ]));

    expect($access->matchesWorkspace($requisition, $workspace))->toBeTrue();
});

it('matches inventory workspaces only for incoming queue requisitions', function (): void {
    $access = new InventoryRequisitionAccess(
        new InventoryLocationAccess(),
        new InventoryRequisitionWorkflow(),
    );
    $workspace = inventoryWorkspace('inventory-requisitions.show');

    $incoming = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Submitted,
    ]);
    $incoming->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::LABORATORY,
    ]));

    $draft = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Draft,
    ]);
    $draft->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::LABORATORY,
    ]));

    expect($access->matchesWorkspace($incoming, $workspace))->toBeTrue()
        ->and($access->matchesWorkspace($draft, $workspace))->toBeFalse();
});

function inventoryWorkspace(string $routeName): InventoryWorkspace
{
    $request = Request::create('/');
    $route = (new Route('GET', '/', static fn () => null))->name($routeName);
    $request->setRouteResolver(static fn (): Route => $route);

    return InventoryWorkspace::fromRequest($request);
}

