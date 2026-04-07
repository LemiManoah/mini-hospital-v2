<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Models\GoodsReceipt;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

final readonly class GoodsReceiptPrintController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:goods_receipts.view', only: ['show']),
        ];
    }

    public function show(Request $request, GoodsReceipt $goodsReceipt): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);

        abort_unless(
            $this->inventoryLocationAccess->canAccessLocation(Auth::user(), $goodsReceipt->inventory_location_id, $goodsReceipt->branch_id),
            403,
            'You do not have access to this goods receipt.',
        );

        abort_unless(
            $workspace->locationTypeValues() === []
                || $this->inventoryLocationAccess->canAccessLocationForTypes(
                    Auth::user(),
                    $goodsReceipt->inventory_location_id,
                    $workspace->locationTypeValues(),
                    $goodsReceipt->branch_id,
                ),
            404,
            'This goods receipt does not belong to the selected inventory workspace.',
        );

        $goodsReceipt->load([
            'branch:id,name,branch_code',
            'purchaseOrder:id,order_number,supplier_id,order_date',
            'purchaseOrder.supplier:id,name',
            'inventoryLocation:id,name,location_code',
            'items.inventoryItem:id,name,generic_name',
            'items.purchaseOrderItem:id,quantity_ordered',
        ]);

        $pdf = Pdf::loadView('print.goods-receipt', [
            'goodsReceipt' => $goodsReceipt,
            'printedAt' => now(),
        ])->setPaper('a4');

        return $pdf->stream(sprintf(
            'goods-receipt-%s.pdf',
            $goodsReceipt->receipt_number,
        ));
    }
}
