<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ApprovePurchaseOrder
{
    public function handle(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        abort_unless(
            $purchaseOrder->status === PurchaseOrderStatus::Submitted,
            422,
            'Only submitted purchase orders can be approved.',
        );

        return DB::transaction(function () use ($purchaseOrder): PurchaseOrder {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::Approved,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            return $purchaseOrder->refresh();
        });
    }
}
