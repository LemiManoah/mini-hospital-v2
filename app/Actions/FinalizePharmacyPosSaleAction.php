<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosCartStatus;
use App\Enums\PharmacyPosSaleStatus;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Models\PharmacyPosSale;
use App\Support\PharmacyPosSaleNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class FinalizePharmacyPosSaleAction
{
    public function __construct(
        private PharmacyPosSaleNumberGenerator $saleNumberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentData
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

            $grossAmount = round($cart->items->sum(
                fn (PharmacyPosCartItem $item): float => round((float) $item->quantity * (float) $item->unit_price, 2)
            ), 2);

            $discountAmount = round($cart->items->sum(
                fn (PharmacyPosCartItem $item): float => (float) $item->discount_amount
            ), 2);

            $totalAmount = max(0.0, round($grossAmount - $discountAmount, 2));

            $paidAmount = max(0.0, round((float) ($paymentData['paid_amount'] ?? $totalAmount), 2));
            $changeAmount = max(0.0, round($paidAmount - $totalAmount, 2));
            $balanceAmount = max(0.0, round($totalAmount - $paidAmount, 2));

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
                if (! $cartItem instanceof PharmacyPosCartItem) {
                    continue;
                }

                $lineTotal = max(0.0, round(
                    round((float) $cartItem->quantity * (float) $cartItem->unit_price, 2) - (float) $cartItem->discount_amount,
                    2
                ));

                $sale->items()->create([
                    'inventory_item_id' => $cartItem->inventory_item_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount_amount' => $cartItem->discount_amount,
                    'line_total' => $lineTotal,
                    'notes' => $cartItem->notes,
                ]);
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

            return $sale->refresh()->load(['items.inventoryItem', 'payments', 'inventoryLocation']);
        });
    }
}
