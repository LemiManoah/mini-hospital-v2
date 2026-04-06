<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use Illuminate\Support\Facades\Auth;

final class CancelInventoryRequisition
{
    public function handle(InventoryRequisition $requisition, string $reason): InventoryRequisition
    {
        $updatedRows = InventoryRequisition::query()
            ->whereKey($requisition->id)
            ->whereIn('status', [
                InventoryRequisitionStatus::Draft,
                InventoryRequisitionStatus::Submitted,
            ])
            ->update([
                'status' => InventoryRequisitionStatus::Cancelled,
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only draft or submitted requisitions can be cancelled.');

        return $requisition->refresh();
    }
}
