<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CancelPurchaseOrder
{
    public function handle(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        abort_unless(
            in_array($purchaseOrder->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Approved], true),
            422,
            'This purchase order cannot be cancelled.',
        );

        return DB::transaction(function () use ($purchaseOrder): PurchaseOrder {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::Cancelled,
                'updated_by' => Auth::id(),
            ]);

            return $purchaseOrder->refresh();
        });
    }
}
