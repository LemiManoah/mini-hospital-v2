<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RefundPharmacyPosSaleAction;
use App\Http\Requests\RefundPharmacyPosSaleRequest;
use App\Models\PharmacyPosSale;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class PharmacyPosSaleRefundController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.refund', only: ['store']),
        ];
    }

    public function store(
        RefundPharmacyPosSaleRequest $request,
        PharmacyPosSale $sale,
        RefundPharmacyPosSaleAction $action,
    ): RedirectResponse {
        abort_unless($sale->branch_id === BranchContext::getActiveBranchId(), 404);

        $action->handle($sale, $request->validated());

        return to_route('pharmacy.pos.sales.show', ['sale' => $sale])
            ->with('success', 'Sale refunded and stock reversed.');
    }
}
