<?php

declare(strict_types=1);

use App\Enums\InventoryLocationType;
use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Support\InventoryRequisitionWorkflow;

it('defines requester and fulfilling location types centrally', function (): void {
    $workflow = new InventoryRequisitionWorkflow();

    expect($workflow->requesterLocationTypes())->toBe([
        InventoryLocationType::PHARMACY->value,
        InventoryLocationType::LABORATORY->value,
    ])->and($workflow->fulfillingLocationTypes())->toBe([
        InventoryLocationType::MAIN_STORE->value,
    ]);
});

it('defines hidden incoming queue statuses centrally', function (): void {
    $workflow = new InventoryRequisitionWorkflow();

    expect($workflow->hiddenIncomingStatuses())->toBe([
        InventoryRequisitionStatus::Draft->value,
        InventoryRequisitionStatus::Cancelled->value,
    ]);
});

it('identifies requester-managed requisitions for the incoming queue', function (): void {
    $workflow = new InventoryRequisitionWorkflow();

    $requisition = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Submitted,
    ]);
    $requisition->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::PHARMACY,
    ]));

    expect($workflow->isIncomingQueueItem($requisition))->toBeTrue();
});

it('excludes draft and cancelled requisitions from the incoming queue', function (): void {
    $workflow = new InventoryRequisitionWorkflow();

    $draft = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Draft,
    ]);
    $draft->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::PHARMACY,
    ]));

    $cancelled = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Cancelled,
    ]);
    $cancelled->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::LABORATORY,
    ]));

    expect($workflow->isIncomingQueueItem($draft))->toBeFalse()
        ->and($workflow->isIncomingQueueItem($cancelled))->toBeFalse();
});

it('excludes non-requester destinations from the incoming queue', function (): void {
    $workflow = new InventoryRequisitionWorkflow();

    $requisition = new InventoryRequisition([
        'status' => InventoryRequisitionStatus::Submitted,
    ]);
    $requisition->setRelation('requestingLocation', new InventoryLocation([
        'type' => InventoryLocationType::MAIN_STORE,
    ]));

    expect($workflow->isIncomingQueueItem($requisition))->toBeFalse();
});
