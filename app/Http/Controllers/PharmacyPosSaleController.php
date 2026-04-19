<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FinalizePharmacyPosSaleAction;
use App\Enums\PharmacyPosCartStatus;
use App\Http\Requests\FinalizePharmacyPosSaleRequest;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosPayment;
use App\Models\PharmacyPosSale;
use App\Models\PharmacyPosSaleItem;
use App\Support\BranchContext;
use App\Support\InventoryNavigationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyPosSaleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.complete', only: ['store']),
            new Middleware('permission:pharmacy_pos.view', only: ['show', 'checkout']),
        ];
    }

    public function checkout(Request $request, PharmacyPosCart $cart): Response
    {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 404);

        $cart->load(['items.inventoryItem', 'inventoryLocation']);

        $grossAmount = round($cart->items->sum(
            fn ($item) => round((float) $item->quantity * (float) $item->unit_price, 2)
        ), 2);
        $discountAmount = round($cart->items->sum(fn ($item) => (float) $item->discount_amount), 2);
        $totalAmount = max(0.0, round($grossAmount - $discountAmount, 2));

        return Inertia::render('pharmacy/pos/checkout', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'cart' => [
                'id' => $cart->id,
                'cart_number' => $cart->cart_number,
                'customer_name' => $cart->customer_name,
                'customer_phone' => $cart->customer_phone,
                'inventory_location' => $cart->inventoryLocation === null ? null : [
                    'id' => $cart->inventoryLocation->id,
                    'name' => $cart->inventoryLocation->name,
                ],
                'items' => $cart->items->map(static fn ($item): array => [
                    'id' => $item->id,
                    'item_name' => $item->inventoryItem?->name,
                    'generic_name' => $item->inventoryItem?->generic_name,
                    'quantity' => round((float) $item->quantity, 3),
                    'unit_price' => round((float) $item->unit_price, 2),
                    'discount_amount' => round((float) $item->discount_amount, 2),
                    'line_total' => max(0.0, round(
                        round((float) $item->quantity * (float) $item->unit_price, 2) - (float) $item->discount_amount,
                        2
                    )),
                ])->values()->all(),
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ],
        ]);
    }

    public function store(
        FinalizePharmacyPosSaleRequest $request,
        PharmacyPosCart $cart,
        FinalizePharmacyPosSaleAction $action,
    ): RedirectResponse {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 403, 'This cart is no longer active.');

        $sale = $action->handle($cart, $request->validated());

        return to_route('pharmacy.pos.sales.show', ['sale' => $sale])
            ->with('success', 'Sale completed. Receipt is ready.');
    }

    public function show(Request $request, PharmacyPosSale $sale): Response
    {
        abort_unless($sale->branch_id === BranchContext::getActiveBranchId(), 404);

        $sale->load(['items.inventoryItem', 'items.allocations.inventoryBatch', 'payments', 'inventoryLocation', 'createdBy.staff']);

        return Inertia::render('pharmacy/pos/sales/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'sale' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'sale_type' => $sale->sale_type,
                'status' => $sale->status?->value,
                'status_label' => $sale->status?->label(),
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'gross_amount' => (float) $sale->gross_amount,
                'discount_amount' => (float) $sale->discount_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance_amount' => (float) $sale->balance_amount,
                'change_amount' => (float) $sale->change_amount,
                'sold_at' => $sale->sold_at?->toISOString(),
                'notes' => $sale->notes,
                'inventory_location' => $sale->inventoryLocation === null ? null : [
                    'id' => $sale->inventoryLocation->id,
                    'name' => $sale->inventoryLocation->name,
                    'location_code' => $sale->inventoryLocation->location_code,
                ],
                'sold_by' => $sale->createdBy?->staff === null
                    ? $sale->createdBy?->email
                    : mb_trim(sprintf('%s %s', $sale->createdBy->staff->first_name, $sale->createdBy->staff->last_name)),
                'items' => $sale->items->map(static fn (PharmacyPosSaleItem $item): array => [
                    'id' => $item->id,
                    'item_name' => $item->inventoryItem?->name,
                    'generic_name' => $item->inventoryItem?->generic_name,
                    'quantity' => round((float) $item->quantity, 3),
                    'unit_price' => round((float) $item->unit_price, 2),
                    'discount_amount' => round((float) $item->discount_amount, 2),
                    'line_total' => round((float) $item->line_total, 2),
                    'notes' => $item->notes,
                ])->values()->all(),
                'payments' => $sale->payments->map(static fn (PharmacyPosPayment $payment): array => [
                    'id' => $payment->id,
                    'amount' => round((float) $payment->amount, 2),
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'payment_date' => $payment->payment_date?->toISOString(),
                    'is_refund' => $payment->is_refund,
                    'notes' => $payment->notes,
                ])->values()->all(),
            ],
        ]);
    }
}
