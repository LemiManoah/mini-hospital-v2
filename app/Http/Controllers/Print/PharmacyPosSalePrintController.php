<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Models\PharmacyPosSale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class PharmacyPosSalePrintController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.view_history', only: ['show']),
        ];
    }

    public function show(PharmacyPosSale $sale): Response
    {
        $sale->load([
            'branch:id,name,branch_code,currency_id',
            'branch.currency:id,code,symbol',
            'inventoryLocation:id,name',
            'createdBy:id,email,staff_id',
            'createdBy.staff:id,first_name,last_name',
            'items.inventoryItem:id,name,generic_name',
            'payments',
        ]);

        $pdf = Pdf::loadView('print.pharmacy-pos-receipt', [
            'sale' => $sale,
            'printedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream(sprintf('pos-receipt-%s.pdf', $sale->sale_number));
    }
}
