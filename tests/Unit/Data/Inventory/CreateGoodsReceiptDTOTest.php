<?php

declare(strict_types=1);

use App\Data\Inventory\CreateGoodsReceiptDTO;

it('normalizes nullable receipt strings and keeps only positive receipt items', function (): void {
    $data = CreateGoodsReceiptDTO::fromRequest([
        'purchase_order_id' => 'po-1',
        'inventory_location_id' => 'location-1',
        'receipt_date' => '2026-04-22',
        'supplier_invoice_number' => '   ',
        'notes' => '  Receive against supplier invoice  ',
        'items' => [
            [
                'purchase_order_item_id' => 'po-item-1',
                'inventory_item_id' => 'item-1',
                'quantity_received' => 0,
                'unit_cost' => '50',
                'batch_number' => '   ',
                'expiry_date' => '   ',
                'notes' => '',
            ],
            [
                'purchase_order_item_id' => 'po-item-2',
                'inventory_item_id' => 'item-2',
                'quantity_received' => '10.5',
                'unit_cost' => '30',
                'batch_number' => ' BATCH-002 ',
                'expiry_date' => '2027-04-22',
                'notes' => '  second line  ',
            ],
        ],
    ], ['pharmacy']);

    expect($data->supplierInvoiceNumber)->toBeNull()
        ->and($data->notes)->toBe('Receive against supplier invoice')
        ->and($data->allowedLocationTypes)->toBe(['pharmacy'])
        ->and($data->items)->toHaveCount(2)
        ->and($data->receiptItems())->toHaveCount(1)
        ->and($data->receiptItems()[0]->purchaseOrderItemId)->toBe('po-item-2')
        ->and($data->receiptItems()[0]->batchNumber)->toBe('BATCH-002')
        ->and($data->receiptItems()[0]->notes)->toBe('second line');
});
