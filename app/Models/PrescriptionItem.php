<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PrescriptionItem extends Model
{
    /** @use HasFactory<\Database\Factories\PrescriptionItemFactory> */
    use HasFactory;

    use HasUuids;
    protected $casts = [
        'prescription_id' => 'string',
        'drug_id' => 'string',
        'duration_days' => 'integer',
        'quantity' => 'integer',
        'is_prn' => 'boolean',
        'is_external_pharmacy' => 'boolean',
        'status' => PrescriptionItemStatus::class,
        'dispensed_at' => 'datetime',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug_id');
    }
}
