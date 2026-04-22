<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosCartStatus;
use App\Enums\PharmacyPosSaleStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Models\PharmacyPosSale;
use App\Models\PharmacyPosSaleItem;
use App\Models\StockMovement;
use App\Support\InventoryStockLedger;
use App\Support\PharmacyPosSaleNumberGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class FinalizePharmacyPosSaleAction
{
    public function __construct(
        private PharmacyPosSaleNumberGenerator $saleNumberGenerator,
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    /**
     * @param  array{
     *   paid_amount?: int|float|string|null,
     *   payment_method?: string|null,
     *   reference_number?: string|null,
     *   notes?: string|null
     * }  $paymentData
     */
    public function handle(PharmacyPosCart $cart, array $paymentData): PharmacyPosSale
    {
        return DB::transaction(function () use ($cart, $paymentData): PharmacyPosSale {
            $cart = PharmacyPosCart::query()
                ->with(['items.inventoryItem'])
                ->lockForUpdate()
                ->findOrFail($cart->id);

            if ($cart->status !== PharmacyPosCartStatus::Active) {
                throw ValidationException::withMessages([
                    'cart' => 'Only active carts can be finalized.',
                ]);
            }

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'The cart has no items to sell.',
                ]);
            }

            $grossAmount = round($cart->items->reduce(
                static fn (float $carry, PharmacyPosCartItem $item): float => $carry + round((float) $item->quantity * (float) $item->unit_price, 2),
                0.0,
            ), 2);

            $discountAmount = round($cart->items->reduce(
                static fn (float $carry, PharmacyPosCartItem $item): float => $carry + (float) $item->discount_amount,
                0.0,
            ), 2);

            $totalAmount = max(0.0, round($grossAmount - $discountAmount, 2));

            $paidAmount = max(0.0, round($this->toFloat($paymentData['paid_amount'] ?? $totalAmount), 2));
            $changeAmount = max(0.0, round($paidAmount - $totalAmount, 2));
            $balanceAmount = max(0.0, round($totalAmount - $paidAmount, 2));

            $availableBatches = $this->loadAvailableBatches($cart->branch_id, $cart->inventory_location_id);

            /** @var Collection<string, float> $availableQuantities */
            $availableQuantities = $availableBatches->mapWithKeys(
                static fn (array $batch): array => [$batch['inventory_batch_id'] => $batch['quantity']]
            );

            $sale = PharmacyPosSale::query()->create([
                'tenant_id' => $cart->tenant_id,
                'branch_id' => $cart->branch_id,
                'inventory_location_id' => $cart->inventory_location_id,
                'pharmacy_pos_cart_id' => $cart->id,
                'sale_number' => $this->saleNumberGenerator->generate($cart->tenant_id),
                'sale_type' => 'walk_in',
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'change_amount' => $changeAmount,
                'customer_name' => $cart->customer_name,
                'customer_phone' => $cart->customer_phone,
                'status' => PharmacyPosSaleStatus::Completed,
                'sold_at' => now(),
                'notes' => $cart->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($cart->items as $cartItem) {
                $lineTotal = max(0.0, round(
                    round((float) $cartItem->quantity * (float) $cartItem->unit_price, 2) - (float) $cartItem->discount_amount,
                    2
                ));

                $saleItem = $sale->items()->create([
                    'inventory_item_id' => $cartItem->inventory_item_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount_amount' => $cartItem->discount_amount,
                    'line_total' => $lineTotal,
                    'notes' => $cartItem->notes,
                ]);

                $this->allocateAndPostStock(
                    $sale,
                    $saleItem,
                    $availableBatches,
                    $availableQuantities,
                );
            }

            if ($paidAmount > 0) {
                $sale->payments()->create([
                    'amount' => $paidAmount,
                    'payment_method' => $paymentData['payment_method'] ?? 'cash',
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'payment_date' => now(),
                    'is_refund' => false,
                    'notes' => $paymentData['notes'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $cart->update([
                'status' => PharmacyPosCartStatus::Converted,
                'converted_at' => now(),
            ]);

            return $sale->refresh()->load(['items.inventoryItem', 'items.allocations', 'payments', 'inventoryLocation']);
        });
    }

    /**
     * @return Collection<string, array{
     *     inventory_batch_id: string,
     *     inventory_item_id: string,
     *     batch_number: string|null,
     *     expiry_date: string|null,
     *     quantity: float,
     *     batch: InventoryBatch
     * }>
     */
    private function loadAvailableBatches(string $branchId, string $locationId): Collection
    {
        $balances = $this->inventoryStockLedger
            ->summarizeByBatch($branchId)
            ->filter(
                static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId
                    && $balance['quantity'] > 0
            )
            ->values();

        $batches = InventoryBatch::query()
            ->whereIn('id', $balances->pluck('inventory_batch_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return $balances
            ->map(function (array $balance) use ($batches): ?array {
                $batchId = $balance['inventory_batch_id'];
                $batch = $batches->get($batchId);

                if (! $batch instanceof InventoryBatch) {
                    return null;
                }

                if ($batch->expiry_date !== null && $batch->expiry_date->startOfDay()->isBefore(today())) {
                    return null;
                }

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'batch' => $batch,
                ];
            })
            ->filter(static fn (?array $batch): bool => is_array($batch))
            ->mapWithKeys(static fn (array $batch): array => [$batch['inventory_batch_id'] => $batch]);
    }

    /**
     * @param  Collection<string, array{
     *   inventory_batch_id: string,
     *   inventory_item_id: string,
     *   batch_number: string|null,
     *   expiry_date: string|null,
     *   quantity: float,
     *   batch: InventoryBatch
     * }>  $availableBatches
     * @param  Collection<string, float>  $availableQuantities
     */
    private function allocateAndPostStock(
        PharmacyPosSale $sale,
        PharmacyPosSaleItem $saleItem,
        Collection $availableBatches,
        Collection $availableQuantities,
    ): void {
        $needed = round((float) $saleItem->quantity, 3);

        $candidates = $availableBatches
            ->filter(
                static fn (array $batch): bool => $batch['inventory_item_id'] === $saleItem->inventory_item_id
                    && (float) $availableQuantities->get($batch['inventory_batch_id'], 0.0) > 0
            )
            ->sortBy(
                static fn (array $batch): string => sprintf(
                    '%s|%s',
                    $batch['expiry_date'] ?? '9999-12-31',
                    $batch['batch_number'] ?? 'ZZZ',
                )
            )
            ->values();

        foreach ($candidates as $batchData) {
            $batchId = $batchData['inventory_batch_id'];
            $available = (float) $availableQuantities->get($batchId, 0.0);
            if ($available <= 0) {
                continue;
            }

            if ($needed <= 0.0005) {
                continue;
            }

            $allocated = round(min($needed, $available), 3);

            /** @var InventoryBatch $batch */
            $batch = $batchData['batch'];

            $saleItem->allocations()->create([
                'inventory_batch_id' => $batch->id,
                'quantity' => $allocated,
                'unit_cost_snapshot' => $batch->unit_cost,
                'batch_number_snapshot' => $batch->batch_number,
                'expiry_date_snapshot' => $batch->expiry_date,
            ]);

            StockMovement::query()->create([
                'tenant_id' => $sale->tenant_id,
                'branch_id' => $sale->branch_id,
                'inventory_location_id' => $sale->inventory_location_id,
                'inventory_item_id' => $saleItem->inventory_item_id,
                'inventory_batch_id' => $batch->id,
                'movement_type' => StockMovementType::PosSale,
                'quantity' => -1 * $allocated,
                'unit_cost' => $batch->unit_cost,
                'source_document_type' => PharmacyPosSale::class,
                'source_document_id' => $sale->id,
                'source_line_type' => PharmacyPosSaleItem::class,
                'source_line_id' => $saleItem->id,
                'notes' => $saleItem->notes,
                'occurred_at' => $sale->sold_at ?? now(),
                'created_by' => Auth::id(),
            ]);

            $availableQuantities->put((string) $batchId, max(0.0, $available - $allocated));
            $needed = round($needed - $allocated, 3);

            if ($needed <= 0.0005) {
                break;
            }
        }

        if ($needed > 0.0005) {
            $inventoryItem = $saleItem->inventoryItem;
            $itemName = $inventoryItem === null
                ? 'one of the items'
                : ($inventoryItem->generic_name ?? $inventoryItem->name);

            throw ValidationException::withMessages([
                'cart' => sprintf('Not enough stock to complete the sale for %s.', $itemName),
            ]);
        }
    }

    private function toFloat(int|float|string|null $value): float
    {
        return (float) ($value ?? 0);
    }
}
