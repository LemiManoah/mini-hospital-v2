<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class SubmitPurchaseOrder
{
    public function handle(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        abort_unless(
            $purchaseOrder->status === PurchaseOrderStatus::Draft,
            422,
            'Only draft purchase orders can be submitted.',
        );

        abort_unless(
            $purchaseOrder->items()->exists(),
            422,
            'Purchase order must have at least one item.',
        );

        return DB::transaction(function () use ($purchaseOrder): PurchaseOrder {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::Submitted,
                'updated_by' => Auth::id(),
            ]);

            return $purchaseOrder->refresh();
        });
    }
}
