<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\PostGoodsReceipt;
use App\Actions\PostStockAdjustment;
use App\Actions\PostStockCount;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockCountStatus;
use App\Models\GoodsReceipt;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\Supplier;
use App\Support\InventoryStockLedger;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;

final class CityGeneralHospitalInventoryWorkflowSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $mainBranch = $tenant ? $this->cityGeneralMainBranch($tenant) : null;
        $creator = $tenant ? $this->cityGeneralRegistrar($tenant) : null;

        if ($tenant === null || $mainBranch === null || $creator === null) {
            return;
        }

        $suppliers = $this->seedSuppliers($tenant->id);
        $locations = InventoryLocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $mainBranch->id)
            ->get()
            ->keyBy('location_code');
        $items = InventoryItem::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy(static fn (InventoryItem $item): string => $item->generic_name ?? $item->name);

        if ($locations->isEmpty() || $items->isEmpty()) {
            return;
        }

        $this->seedReceiptWorkflow(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            userId: $creator->id,
            suppliers: $suppliers,
            locations: $locations->all(),
            items: $items->all(),
        );

        $this->seedAdjustments(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            userId: $creator->id,
            locations: $locations->all(),
            items: $items->all(),
        );

        $this->seedCounts(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            userId: $creator->id,
            locations: $locations->all(),
            items: $items->all(),
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
        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['medical'],
            location: $locations['CGH-MAIN-STORE'] ?? null,
            orderNumber: 'CGH-PO-INV-001',
            receiptNumber: 'CGH-GR-INV-001',
            itemLines: [
                ['item' => $items['Paracetamol'] ?? null, 'quantity' => 1500, 'unit_cost' => 120, 'batch' => 'CGH-PARA-202604', 'expiry' => now()->addMonths(16)->toDateString()],
                ['item' => $items['Amoxicillin'] ?? null, 'quantity' => 800, 'unit_cost' => 180, 'batch' => 'CGH-AMOX-202604', 'expiry' => now()->addMonths(14)->toDateString()],
                ['item' => $items['Examination Gloves'] ?? null, 'quantity' => 180, 'unit_cost' => 18000, 'batch' => 'CGH-GLOVE-202604', 'expiry' => now()->addMonths(10)->toDateString()],
            ],
        );

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['medical'],
            location: $locations['CGH-MAIN-PHARM'] ?? null,
            orderNumber: 'CGH-PO-INV-002',
            receiptNumber: 'CGH-GR-INV-002',
            itemLines: [
                ['item' => $items['Paracetamol'] ?? null, 'quantity' => 500, 'unit_cost' => 120, 'batch' => 'CGH-PARA-PH-202604', 'expiry' => now()->addMonths(12)->toDateString()],
                ['item' => $items['Omeprazole'] ?? null, 'quantity' => 250, 'unit_cost' => 220, 'batch' => 'CGH-OMEP-202604', 'expiry' => now()->addMonths(15)->toDateString()],
            ],
        );

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['laboratory'],
            location: $locations['CGH-MAIN-LAB'] ?? null,
            orderNumber: 'CGH-PO-INV-003',
            receiptNumber: 'CGH-GR-INV-003',
            itemLines: [
                ['item' => $items['CBC Reagent Pack'] ?? null, 'quantity' => 18, 'unit_cost' => 145000, 'batch' => 'CGH-CBC-202604', 'expiry' => now()->addMonths(8)->toDateString()],
                ['item' => $items['Malaria Rapid Test Kit'] ?? null, 'quantity' => 90, 'unit_cost' => 9500, 'batch' => 'CGH-MRDT-202604', 'expiry' => now()->addMonths(7)->toDateString()],
            ],
        );

        $this->ensurePostedReceipt(
            tenantId: $tenantId,
            branchId: $branchId,
            userId: $userId,
            supplier: $suppliers['medical'],
            location: $locations['CGH-MAIN-PROC'] ?? null,
            orderNumber: 'CGH-PO-INV-004',
            receiptNumber: 'CGH-GR-INV-004',
            itemLines: [
                ['item' => $items['Ceftriaxone'] ?? null, 'quantity' => 40, 'unit_cost' => 2500, 'batch' => 'CGH-CEF-202604', 'expiry' => now()->addMonths(11)->toDateString()],
                ['item' => $items['5ml Syringe'] ?? null, 'quantity' => 140, 'unit_cost' => 250, 'batch' => 'CGH-SYR-202604', 'expiry' => now()->addMonths(9)->toDateString()],
            ],
        );
    }

    /**
     * @param  array<int, array{item: InventoryItem|null, quantity: int|float, unit_cost: int|float, batch: string, expiry: string}>  $itemLines
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

            PurchaseOrderItem::query()->firstOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_item_id' => $line['item']->id,
                ],
                [
                    'quantity_ordered' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'total_cost' => (float) $line['quantity'] * (float) $line['unit_cost'],
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

            $goodsReceipt->items()->firstOrCreate(
                [
                    'purchase_order_item_id' => $purchaseOrderItem->id,
                    'inventory_item_id' => $line['item']->id,
                ],
                [
                    'quantity_received' => $line['quantity'],
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
    private function seedAdjustments(
        string $tenantId,
        string $branchId,
        string $userId,
        array $locations,
        array $items,
    ): void {
        $pharmacyLocation = $locations['CGH-MAIN-PHARM'] ?? null;
        $labLocation = $locations['CGH-MAIN-LAB'] ?? null;
        $paracetamol = $items['Paracetamol'] ?? null;
        $malariaKit = $items['Malaria Rapid Test Kit'] ?? null;

        if (
            ! $pharmacyLocation instanceof InventoryLocation
            || ! $labLocation instanceof InventoryLocation
            || ! $paracetamol instanceof InventoryItem
            || ! $malariaKit instanceof InventoryItem
        ) {
            return;
        }

        $paracetamolBatch = InventoryBatch::query()
            ->where('inventory_location_id', $pharmacyLocation->id)
            ->where('inventory_item_id', $paracetamol->id)
            ->orderBy('received_at')
            ->first();

        if ($paracetamolBatch instanceof InventoryBatch) {
            $postedAdjustment = StockAdjustment::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'adjustment_number' => 'CGH-ADJ-001',
                ],
                [
                    'branch_id' => $branchId,
                    'inventory_location_id' => $pharmacyLocation->id,
                    'status' => StockAdjustmentStatus::Draft,
                    'adjustment_date' => now()->subDay()->toDateString(),
                    'reason' => 'Damaged blister packs removed from pharmacy shelf.',
                    'notes' => 'Seeded posted adjustment for manual inventory testing.',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );

            $postedAdjustment->items()->firstOrCreate(
                [
                    'inventory_item_id' => $paracetamol->id,
                ],
                [
                    'inventory_batch_id' => $paracetamolBatch->id,
                    'quantity_delta' => -12,
                    'unit_cost' => 120,
                    'notes' => 'Packaging damaged during shelf review.',
                ],
            );

            if ($postedAdjustment->status === StockAdjustmentStatus::Draft) {
                resolve(PostStockAdjustment::class)->handle($postedAdjustment);
            }
        }

        StockAdjustment::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'adjustment_number' => 'CGH-ADJ-002',
            ],
            [
                'branch_id' => $branchId,
                'inventory_location_id' => $labLocation->id,
                'status' => StockAdjustmentStatus::Draft,
                'adjustment_date' => now()->toDateString(),
                'reason' => 'Laboratory verification stock gain awaiting post.',
                'notes' => 'Seeded draft adjustment so the draft workflow can be tested manually.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        )->items()->firstOrCreate(
            [
                'inventory_item_id' => $malariaKit->id,
            ],
            [
                'inventory_batch_id' => null,
                'quantity_delta' => 6,
                'unit_cost' => 9500,
                'batch_number' => 'CGH-MRDT-ADJ-001',
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'notes' => 'Additional kits found during shelf consolidation.',
            ],
        );
    }

    /**
     * @param  array<string, InventoryLocation>  $locations
     * @param  array<string, InventoryItem>  $items
     */
    private function seedCounts(
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

        $pharmacyLocation = $locations['CGH-MAIN-PHARM'] ?? null;
        $storeLocation = $locations['CGH-MAIN-STORE'] ?? null;
        $paracetamol = $items['Paracetamol'] ?? null;
        $amoxicillin = $items['Amoxicillin'] ?? null;

        if (
            ! $pharmacyLocation instanceof InventoryLocation
            || ! $storeLocation instanceof InventoryLocation
            || ! $paracetamol instanceof InventoryItem
            || ! $amoxicillin instanceof InventoryItem
        ) {
            return;
        }

        $postedCount = StockCount::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'count_number' => 'CGH-CNT-001',
            ],
            [
                'branch_id' => $branchId,
                'inventory_location_id' => $pharmacyLocation->id,
                'status' => StockCountStatus::Draft,
                'count_date' => now()->toDateString(),
                'notes' => 'Seeded posted count for pharmacy shelf reconciliation.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );

        $pharmacyExpected = (float) ($locationBalances[$pharmacyLocation->id.'|'.$paracetamol->id] ?? 0.0);

        $postedCount->items()->firstOrCreate(
            [
                'inventory_item_id' => $paracetamol->id,
            ],
            [
                'expected_quantity' => $pharmacyExpected,
                'counted_quantity' => max($pharmacyExpected - 3, 0),
                'variance_quantity' => max($pharmacyExpected - 3, 0) - $pharmacyExpected,
                'notes' => 'Three packs could not be accounted for during count.',
            ],
        );

        if ($postedCount->status === StockCountStatus::Draft) {
            resolve(PostStockCount::class)->handle($postedCount);
        }

        $storeExpected = (float) ($locationBalances[$storeLocation->id.'|'.$amoxicillin->id] ?? 0.0);

        $draftCount = StockCount::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'count_number' => 'CGH-CNT-002',
            ],
            [
                'branch_id' => $branchId,
                'inventory_location_id' => $storeLocation->id,
                'status' => StockCountStatus::Draft,
                'count_date' => now()->toDateString(),
                'notes' => 'Seeded draft count so the posting flow can be tested manually.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );

        $draftCount->items()->firstOrCreate(
            [
                'inventory_item_id' => $amoxicillin->id,
            ],
            [
                'expected_quantity' => $storeExpected,
                'counted_quantity' => $storeExpected,
                'variance_quantity' => 0,
                'notes' => 'Awaiting manual confirmation before post.',
            ],
        );
    }
}
