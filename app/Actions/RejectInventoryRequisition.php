<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use Illuminate\Support\Facades\Auth;

final class RejectInventoryRequisition
{
    public function handle(InventoryRequisition $requisition, string $reason): InventoryRequisition
    {
        $updatedRows = InventoryRequisition::query()
            ->whereKey($requisition->id)
            ->where('status', InventoryRequisitionStatus::Submitted)
            ->update([
                'status' => InventoryRequisitionStatus::Rejected,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only submitted requisitions can be rejected.');

        return $requisition->refresh();
    }
}
