<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\PostGoodsReceipt;
use App\Actions\PostInventoryReconciliation;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryRequisitionStatus;
use App\Enums\Priority;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ReconciliationStatus;
use App\Models\FacilityBranch;
use App\Models\GoodsReceipt;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Reconciliation;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Support\InventoryStockLedger;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class CityGeneralHospitalInventoryWorkflowSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $mainBranch = $tenant instanceof Tenant ? $this->cityGeneralMainBranch($tenant) : null;
        $creator = $tenant instanceof Tenant ? $this->cityGeneralRegistrar($tenant) : null;

        if (! $tenant instanceof Tenant || ! $mainBranch instanceof FacilityBranch || ! $creator instanceof User) {
            return;
        }

        $suppliers = $this->seedSuppliers($tenant->id);
        /** @var Collection<string, InventoryLocation> $locations */
        $locations = InventoryLocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $mainBranch->id)
            ->get()
            ->keyBy('location_code');
        /** @var Collection<string, InventoryItem> $items */
        $items = InventoryItem::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy(static fn (InventoryItem $item): string => $item->generic_name ?? $item->name);

        if ($locations->isEmpty() || $items->isEmpty()) {
            return;
        }

        /** @var array<string, InventoryLocation> $locationsByCode */
        $locationsByCode = $locations->all();
        /** @var array<string, InventoryItem> $itemsByName */
        $itemsByName = $items->all();

        $this->seedReceiptWorkflow(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            userId: $creator->id,
            suppliers: $suppliers,
            locations: $locationsByCode,
            items: $itemsByName,
        );

        $this->seedReconciliations(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            userId: $creator->id,
            locations: $locationsByCode,
            items: $itemsByName,
        );

        $this->seedRequisitions(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            userId: $creator->id,
            locations: $locationsByCode,
            items: $itemsByName,
        );
    }

    /**
     * @return array<string, Supplier>
     */
    private function seedSuppliers(string $tenantId): array
    {
        return collect([
            'medical' => [
                'name' => 'MedCore Supplies Limited',
                'phone' => '+256 312 410001',
                'email' => 'orders@medcore.ug',
            ],
            'laboratory' => [
                'name' => 'LabCare Diagnostics Uganda',
                'phone' => '+256 312 410002',
                'email' => 'sales@labcare.ug',
            ],
        ])->mapWithKeys(static function (array $supplier, string $key) use ($tenantId): array {
            $model = Supplier::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $supplier['name'],
                ],
                [
                    'phone' => $supplier['phone'],
                    'email' => $supplier['email'],
                    'is_active' => true,
                ],
            );

            return [$key => $model];
        })->all();
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, InventoryLocation>  $locations
     * @param  array<string, InventoryItem>  $items
     */
    private function seedReceiptWorkflow(
        string $tenantId,
        string $branchId,
        string $userId,
        array $suppliers,
        array $locations,
        array $items,
    ): void {
        $mainStore = $locations['CGH-MAIN-STORE'] ?? null;

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['medical'],
            location: $mainStore,
            orderNumber: 'CGH-PO-INV-001',
            receiptNumber: 'CGH-GR-INV-001',
            itemLines: [
                ['item' => $items['Paracetamol'] ?? null, 'ordered_quantity' => 1500, 'received_quantity' => 1500, 'unit_cost' => 120, 'batch' => 'CGH-PARA-202604', 'expiry' => now()->addMonths(16)->toDateString()],
                ['item' => $items['Amoxicillin'] ?? null, 'ordered_quantity' => 800, 'received_quantity' => 800, 'unit_cost' => 180, 'batch' => 'CGH-AMOX-202604', 'expiry' => now()->addMonths(14)->toDateString()],
                ['item' => $items['Examination Gloves'] ?? null, 'ordered_quantity' => 240, 'received_quantity' => 180, 'unit_cost' => 18000, 'batch' => 'CGH-GLOVE-202604', 'expiry' => now()->addMonths(10)->toDateString()],
            ],
        );

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['medical'],
            location: $mainStore,
            orderNumber: 'CGH-PO-INV-002',
            receiptNumber: 'CGH-GR-INV-002',
            itemLines: [
                ['item' => $items['Omeprazole'] ?? null, 'ordered_quantity' => 400, 'received_quantity' => 250, 'unit_cost' => 220, 'batch' => 'CGH-OMEP-202604', 'expiry' => now()->addMonths(15)->toDateString()],
                ['item' => $items['5ml Syringe'] ?? null, 'ordered_quantity' => 320, 'received_quantity' => 160, 'unit_cost' => 250, 'batch' => 'CGH-SYR-202604', 'expiry' => now()->addMonths(9)->toDateString()],
                ['item' => $items['Sterile Gauze Roll'] ?? null, 'ordered_quantity' => 140, 'received_quantity' => 80, 'unit_cost' => 3500, 'batch' => 'CGH-GAUZE-202604', 'expiry' => now()->addMonths(11)->toDateString()],
            ],
        );

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['laboratory'],
            location: $mainStore,
            orderNumber: 'CGH-PO-INV-003',
            receiptNumber: 'CGH-GR-INV-003',
            itemLines: [
                ['item' => $items['CBC Reagent Pack'] ?? null, 'ordered_quantity' => 24, 'received_quantity' => 18, 'unit_cost' => 145000, 'batch' => 'CGH-CBC-202604', 'expiry' => now()->addMonths(8)->toDateString()],
                ['item' => $items['Malaria Rapid Test Kit'] ?? null, 'ordered_quantity' => 120, 'received_quantity' => 90, 'unit_cost' => 9500, 'batch' => 'CGH-MRDT-202604', 'expiry' => now()->addMonths(7)->toDateString()],
            ],
        );

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['medical'],
            location: $mainStore,
            orderNumber: 'CGH-PO-INV-004',
            receiptNumber: 'CGH-GR-INV-004',
            itemLines: [
                ['item' => $items['Ceftriaxone'] ?? null, 'ordered_quantity' => 80, 'received_quantity' => 40, 'unit_cost' => 2500, 'batch' => 'CGH-CEF-202604', 'expiry' => now()->addMonths(11)->toDateString()],
                ['item' => $items['Sharps Container'] ?? null, 'ordered_quantity' => 24, 'received_quantity' => 12, 'unit_cost' => 9500, 'batch' => 'CGH-SHARP-202604', 'expiry' => null],
            ],
        );
    }

    /**
     * @param  array<int, array{
     *     item: InventoryItem|null,
     *     ordered_quantity: int|float,
     *     received_quantity: int|float,
     *     unit_cost: int|float,
     *     batch: string,
     *     expiry: string|null
     * }>  $itemLines
     */
    private function ensurePostedReceipt(
        string $tenantId,
        string $branchId,
        string $userId,
        Supplier $supplier,
        ?InventoryLocation $location,
        string $orderNumber,
        string $receiptNumber,
        array $itemLines,
    ): void {
        if (! $location instanceof InventoryLocation) {
            return;
        }

        $purchaseOrder = PurchaseOrder::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'order_number' => $orderNumber,
            ],
            [
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'status' => PurchaseOrderStatus::Approved,
                'order_date' => now()->subDays(5)->toDateString(),
                'expected_delivery_date' => now()->subDays(2)->toDateString(),
                'notes' => 'Seeded inventory workflow purchase order.',
                'approved_by' => $userId,
                'approved_at' => now()->subDays(5),
                'created_by' => $userId,
                'updated_by' => $userId,
                'total_amount' => 0,
            ],
        );

        foreach ($itemLines as $line) {
            if (! $line['item'] instanceof InventoryItem) {
                continue;
            }

            PurchaseOrderItem::query()->updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_item_id' => $line['item']->id,
                ],
                [
                    'quantity_ordered' => $line['ordered_quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'total_cost' => (float) $line['ordered_quantity'] * (float) $line['unit_cost'],
                ],
            );
        }

        $purchaseOrder->recalculateTotal();

        $goodsReceipt = GoodsReceipt::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'receipt_number' => $receiptNumber,
            ],
            [
                'branch_id' => $branchId,
                'purchase_order_id' => $purchaseOrder->id,
                'inventory_location_id' => $location->id,
                'status' => GoodsReceiptStatus::Draft,
                'receipt_date' => now()->subDays(2)->toDateString(),
                'supplier_invoice_number' => $receiptNumber.'-INV',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );

        foreach ($itemLines as $line) {
            if (! $line['item'] instanceof InventoryItem) {
                continue;
            }

            $purchaseOrderItem = PurchaseOrderItem::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->where('inventory_item_id', $line['item']->id)
                ->first();

            if (! $purchaseOrderItem instanceof PurchaseOrderItem) {
                continue;
            }

            $goodsReceipt->items()->updateOrCreate(
                [
                    'purchase_order_item_id' => $purchaseOrderItem->id,
                    'inventory_item_id' => $line['item']->id,
                ],
                [
                    'quantity_received' => $line['received_quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'batch_number' => $line['batch'],
                    'expiry_date' => $line['expiry'],
                    'notes' => 'Seeded inventory receipt line.',
                ],
            );
        }

        if ($goodsReceipt->status === GoodsReceiptStatus::Draft) {
            resolve(PostGoodsReceipt::class)->handle($goodsReceipt->load('items.purchaseOrderItem'));
        }
    }

    /**
     * @param  array<string, InventoryLocation>  $locations
     * @param  array<string, InventoryItem>  $items
     */
    private function seedReconciliations(
        string $tenantId,
        string $branchId,
        string $userId,
        array $locations,
        array $items,
    ): void {
        $ledger = resolve(InventoryStockLedger::class);
        $locationBalances = $ledger
            ->summarizeByLocation($branchId)
            ->mapWithKeys(static fn (array $balance): array => [
                $balance['inventory_location_id'].'|'.$balance['inventory_item_id'] => $balance['quantity'],
            ]);

        $storeLocation = $locations['CGH-MAIN-STORE'] ?? null;
        $paracetamol = $items['Paracetamol'] ?? null;
        $amoxicillin = $items['Amoxicillin'] ?? null;
        $gloves = $items['Examination Gloves'] ?? null;
        $cbcReagent = $items['CBC Reagent Pack'] ?? null;

        if (
            ! $storeLocation instanceof InventoryLocation
            || ! $paracetamol instanceof InventoryItem
            || ! $amoxicillin instanceof InventoryItem
            || ! $gloves instanceof InventoryItem
            || ! $cbcReagent instanceof InventoryItem
        ) {
            return;
        }

        $paracetamolBatch = InventoryBatch::query()
            ->where('inventory_location_id', $storeLocation->id)
            ->where('inventory_item_id', $paracetamol->id)
            ->oldest('received_at')
            ->first();

        if ($paracetamolBatch instanceof InventoryBatch) {
            $storeParacetamolExpected = (float) ($locationBalances[$storeLocation->id.'|'.$paracetamol->id] ?? 0.0);

            $postedReconciliation = Reconciliation::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'adjustment_number' => 'CGH-REC-001',
                ],
                [
                    'branch_id' => $branchId,
                    'inventory_location_id' => $storeLocation->id,
                    'status' => ReconciliationStatus::Draft,
                    'adjustment_date' => now()->subDay()->toDateString(),
                    'reason' => 'Main store shelf reconciliation after damage review.',
                    'notes' => 'Seeded posted reconciliation for manual testing.',
                    'submitted_by' => $userId,
                    'submitted_at' => now()->subDay(),
                    'reviewed_by' => $userId,
                    'reviewed_at' => now()->subDay(),
                    'review_notes' => 'Shelf variance confirmed during review.',
                    'approved_by' => $userId,
                    'approved_at' => now()->subDay(),
                    'approval_notes' => 'Approved for posting after pharmacist review.',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );

            $postedReconciliation->items()->firstOrCreate(
                [
                    'inventory_item_id' => $paracetamol->id,
                ],
                [
                    'inventory_batch_id' => $paracetamolBatch->id,
                    'expected_quantity' => $storeParacetamolExpected,
                    'actual_quantity' => max($storeParacetamolExpected - 3, 0),
                    'variance_quantity' => max($storeParacetamolExpected - 3, 0) - $storeParacetamolExpected,
                    'quantity_delta' => max($storeParacetamolExpected - 3, 0) - $storeParacetamolExpected,
                    'unit_cost' => 120,
                    'notes' => 'Three packs were damaged during handling in main store.',
                ],
            );

            if ($postedReconciliation->status === ReconciliationStatus::Draft) {
                resolve(PostInventoryReconciliation::class)->handle($postedReconciliation);
            }
        }

        $storeGloveExpected = (float) ($locationBalances[$storeLocation->id.'|'.$gloves->id] ?? 0.0);

        $approvedReconciliation = Reconciliation::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'adjustment_number' => 'CGH-REC-002',
            ],
            [
                'branch_id' => $branchId,
                'inventory_location_id' => $storeLocation->id,
                'status' => ReconciliationStatus::Draft,
                'adjustment_date' => now()->toDateString(),
                'reason' => 'Main store consumables shelf verification awaiting post.',
                'notes' => 'Seeded approved reconciliation so the final posting step can be tested manually.',
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'review_notes' => 'Review confirms additional kits were found.',
                'approved_by' => $userId,
                'approved_at' => now(),
                'approval_notes' => 'Approved for posting.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );

        $approvedReconciliation->items()->firstOrCreate(
            [
                'inventory_item_id' => $gloves->id,
            ],
            [
                'inventory_batch_id' => null,
                'expected_quantity' => $storeGloveExpected,
                'actual_quantity' => $storeGloveExpected + 6,
                'variance_quantity' => 6,
                'quantity_delta' => 6,
                'unit_cost' => 18000,
                'batch_number' => 'CGH-GLOVE-REC-001',
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'notes' => 'Additional glove stock found during shelf consolidation.',
            ],
        );

        $storeExpected = (float) ($locationBalances[$storeLocation->id.'|'.$cbcReagent->id] ?? 0.0);

        Reconciliation::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'adjustment_number' => 'CGH-REC-003',
            ],
            [
                'branch_id' => $branchId,
                'inventory_location_id' => $storeLocation->id,
                'status' => ReconciliationStatus::Draft,
                'adjustment_date' => now()->toDateString(),
                'reason' => 'Main store cycle-check draft.',
                'notes' => 'Seeded draft reconciliation so the full workflow can be tested manually.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        )->items()->firstOrCreate(
            [
                'inventory_item_id' => $cbcReagent->id,
            ],
            [
                'expected_quantity' => $storeExpected,
                'actual_quantity' => $storeExpected,
                'variance_quantity' => 0,
                'quantity_delta' => 0,
                'unit_cost' => 145000,
                'notes' => 'Awaiting submit and review.',
            ],
        );
    }

    /**
     * @param  array<string, InventoryLocation>  $locations
     * @param  array<string, InventoryItem>  $items
     */
    private function seedRequisitions(
        string $tenantId,
        string $branchId,
        string $userId,
        array $locations,
        array $items,
    ): void {
        $storeLocation = $locations['CGH-MAIN-STORE'] ?? null;
        $pharmacyLocation = $locations['CGH-MAIN-PHARM'] ?? null;
        $labLocation = $locations['CGH-MAIN-LAB'] ?? null;
        $paracetamol = $items['Paracetamol'] ?? null;
        $amoxicillin = $items['Amoxicillin'] ?? null;
        $gloves = $items['Examination Gloves'] ?? null;
        $cbcReagent = $items['CBC Reagent Pack'] ?? null;

        if (
            ! $storeLocation instanceof InventoryLocation
            || ! $pharmacyLocation instanceof InventoryLocation
            || ! $labLocation instanceof InventoryLocation
            || ! $paracetamol instanceof InventoryItem
            || ! $amoxicillin instanceof InventoryItem
            || ! $gloves instanceof InventoryItem
            || ! $cbcReagent instanceof InventoryItem
        ) {
            return;
        }

        InventoryRequisition::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'requisition_number' => 'CGH-REQ-001',
            ],
            [
                'branch_id' => $branchId,
                'source_inventory_location_id' => $storeLocation->id,
                'destination_inventory_location_id' => $pharmacyLocation->id,
                'status' => InventoryRequisitionStatus::Draft,
                'priority' => Priority::URGENT,
                'requisition_date' => now()->toDateString(),
                'notes' => 'Seeded draft requisition for manual testing.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        )->items()->firstOrCreate(
            [
                'inventory_item_id' => $paracetamol->id,
            ],
            [
                'requested_quantity' => 30,
                'approved_quantity' => 0,
                'issued_quantity' => 0,
                'notes' => 'Awaiting submit and approval.',
            ],
        );

        InventoryRequisition::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'requisition_number' => 'CGH-REQ-002',
            ],
            [
                'branch_id' => $branchId,
                'source_inventory_location_id' => $storeLocation->id,
                'destination_inventory_location_id' => $pharmacyLocation->id,
                'status' => InventoryRequisitionStatus::Submitted,
                'priority' => Priority::ROUTINE,
                'requisition_date' => now()->toDateString(),
                'notes' => 'Seeded submitted pharmacy requisition awaiting approval.',
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        )->items()->firstOrCreate(
            [
                'inventory_item_id' => $amoxicillin->id,
            ],
            [
                'requested_quantity' => 12,
                'approved_quantity' => 0,
                'issued_quantity' => 0,
                'notes' => 'Pending review.',
            ],
        );

        InventoryRequisition::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'requisition_number' => 'CGH-REQ-003',
            ],
            [
                'branch_id' => $branchId,
                'source_inventory_location_id' => $storeLocation->id,
                'destination_inventory_location_id' => $labLocation->id,
                'status' => InventoryRequisitionStatus::Draft,
                'priority' => Priority::ROUTINE,
                'requisition_date' => now()->toDateString(),
                'notes' => 'Seeded laboratory draft requisition for manual testing.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        )->items()->firstOrCreate(
            [
                'inventory_item_id' => $gloves->id,
            ],
            [
                'requested_quantity' => 10,
                'approved_quantity' => 0,
                'issued_quantity' => 0,
                'notes' => 'Awaiting submit and approval.',
            ],
        );

        InventoryRequisition::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'requisition_number' => 'CGH-REQ-004',
            ],
            [
                'branch_id' => $branchId,
                'source_inventory_location_id' => $storeLocation->id,
                'destination_inventory_location_id' => $labLocation->id,
                'status' => InventoryRequisitionStatus::Approved,
                'priority' => Priority::URGENT,
                'requisition_date' => now()->toDateString(),
                'notes' => 'Seeded approved laboratory requisition ready for issue.',
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'approved_by' => $userId,
                'approved_at' => now(),
                'approval_notes' => 'Approved for issue.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        )->items()->firstOrCreate(
            [
                'inventory_item_id' => $cbcReagent->id,
            ],
            [
                'requested_quantity' => 4,
                'approved_quantity' => 4,
                'issued_quantity' => 0,
                'notes' => 'Seeded approved line ready for issue.',
            ],
        );
    }
}
