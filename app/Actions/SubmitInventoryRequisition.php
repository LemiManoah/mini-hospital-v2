<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use Illuminate\Support\Facades\Auth;

final class SubmitInventoryRequisition
{
    public function handle(InventoryRequisition $requisition): InventoryRequisition
    {
        $updatedRows = InventoryRequisition::query()
            ->whereKey($requisition->id)
            ->where('status', InventoryRequisitionStatus::Draft)
            ->update([
                'status' => InventoryRequisitionStatus::Submitted,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only draft requisitions can be submitted.');

        return $requisition->refresh();
    }
}
