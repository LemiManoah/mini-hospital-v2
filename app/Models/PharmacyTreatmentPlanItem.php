<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PharmacyTreatmentPlanItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'pharmacy_treatment_plan_id' => 'string',
        'prescription_item_id' => 'string',
        'inventory_item_id' => 'string',
        'authorized_total_quantity' => 'decimal:3',
        'quantity_per_cycle' => 'decimal:3',
        'total_cycles' => 'integer',
        'completed_cycles' => 'integer',
    ];

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(PharmacyTreatmentPlan::class, 'pharmacy_treatment_plan_id');
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
