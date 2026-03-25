<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Models\LabRequestItem;
use App\Models\LabRequestItemConsumable;
use Illuminate\Support\Facades\DB;

final readonly class RecordLabRequestItemConsumable
{
    public function __construct(
        private SyncLabRequestItemActualCost $syncLabRequestItemActualCost,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        LabRequestItem $labRequestItem,
        array $attributes,
        ?string $staffId,
    ): LabRequestItemConsumable {
        return DB::transaction(function () use ($labRequestItem, $attributes, $staffId): LabRequestItemConsumable {
            $quantity = (float) $attributes['quantity'];
            $unitCost = (float) $attributes['unit_cost'];

            $consumable = $labRequestItem->consumables()->create([
                'tenant_id' => $labRequestItem->request->tenant_id,
                'facility_branch_id' => $labRequestItem->request->facility_branch_id,
                'consumable_name' => $attributes['consumable_name'],
                'unit_label' => $attributes['unit_label'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_cost' => $quantity * $unitCost,
                'notes' => $attributes['notes'] ?? null,
                'used_at' => $attributes['used_at'] ?? now(),
                'recorded_by' => $staffId,
            ]);

            if ($labRequestItem->status === LabRequestItemStatus::PENDING) {
                $labRequestItem->forceFill([
                    'status' => LabRequestItemStatus::IN_PROGRESS,
                ])->save();
            }

            $this->syncLabRequestItemActualCost->handle($labRequestItem);

            return $consumable->refresh();
        });
    }
}
