<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabRequestItemStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class LabRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\LabRequestItemFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'request_id' => 'string',
        'test_id' => 'string',
        'status' => LabRequestItemStatus::class,
        'price' => 'float',
        'actual_cost' => 'float',
        'costed_at' => 'datetime',
        'is_external' => 'boolean',
        'received_by' => 'string',
        'received_at' => 'datetime',
        'result_entered_by' => 'string',
        'result_entered_at' => 'datetime',
        'reviewed_by' => 'string',
        'reviewed_at' => 'datetime',
        'approved_by' => 'string',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'workflow_stage',
        'result_visible',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'request_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'test_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(LabRequestItemConsumable::class, 'lab_request_item_id');
    }

    public function resultEntry(): HasOne
    {
        return $this->hasOne(LabResultEntry::class, 'lab_request_item_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by');
    }

    public function resultEnteredBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'result_entered_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    protected function workflowStage(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status === LabRequestItemStatus::CANCELLED) {
                return 'cancelled';
            }

            if ($this->approved_at !== null) {
                return 'approved';
            }

            if ($this->reviewed_at !== null) {
                return 'reviewed';
            }

            if ($this->result_entered_at !== null) {
                return 'result_entered';
            }

            if ($this->received_at !== null) {
                return 'received';
            }

            return 'pending';
        });
    }

    protected function resultVisible(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->approved_at !== null && $this->status === LabRequestItemStatus::COMPLETED,
        );
    }
}
