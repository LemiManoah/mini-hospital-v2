<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordPharmacyPosPaymentAction;
use App\Http\Requests\StorePharmacyPosPaymentRequest;
use App\Models\PharmacyPosSale;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class PharmacyPosPaymentController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.complete'),
        ];
    }

    public function store(
        StorePharmacyPosPaymentRequest $request,
        PharmacyPosSale $sale,
        RecordPharmacyPosPaymentAction $action,
    ): RedirectResponse {
        abort_unless($sale->branch_id === BranchContext::getActiveBranchId(), 404);

        $action->handle($sale, $request->validated());

        return back()->with('success', 'Payment recorded successfully.');
    }
}
