<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\VoidPharmacyPosSaleAction;
use App\Models\PharmacyPosSale;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class PharmacyPosSaleVoidController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.void', only: ['store']),
        ];
    }

    public function store(PharmacyPosSale $sale, VoidPharmacyPosSaleAction $action): RedirectResponse
    {
        abort_unless($sale->branch_id === BranchContext::getActiveBranchId(), 404);

        $action->handle($sale);

        return to_route('pharmacy.pos.sales.show', ['sale' => $sale])
            ->with('success', 'Sale voided and stock reversed.');
    }
}
