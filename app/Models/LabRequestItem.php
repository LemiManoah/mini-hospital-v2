<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $request_id
 * @property-read string $test_id
 * @property-read LabRequestItemStatus $status
 * @property-read float|null $price
 * @property-read float|null $actual_cost
 * @property-read bool $is_external
 * @property-read Carbon|null $costed_at
 * @property-read Carbon|null $received_at
 * @property-read Carbon|null $result_entered_at
 * @property-read Carbon|null $reviewed_at
 * @property-read Carbon|null $approved_at
 * @property-read Carbon|null $completed_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read string $workflow_stage
 * @property-read bool $result_visible
 * @property-read LabRequest|null $request
 * @property-read LabTestCatalog|null $test
 * @property-read LabResultEntry|null $resultEntry
 * @property-read LabSpecimen|null $specimen
 */
final class LabRequestItem extends Model
{
    /** @use HasFactory<Factory> */
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

    /** @return BelongsTo<LabRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'request_id');
    }

    /** @return BelongsTo<LabTestCatalog, $this> */
    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'test_id');
    }

    /** @return HasMany<LabRequestItemConsumable, $this> */
    public function consumables(): HasMany
    {
        return $this->hasMany(LabRequestItemConsumable::class, 'lab_request_item_id');
    }

    /** @return HasOne<LabResultEntry, $this> */
    public function resultEntry(): HasOne
    {
        return $this->hasOne(LabResultEntry::class, 'lab_request_item_id');
    }

    /** @return HasOne<LabSpecimen, $this> */
    public function specimen(): HasOne
    {
        return $this->hasOne(LabSpecimen::class, 'lab_request_item_id');
    }

    /** @return BelongsTo<Staff, $this> */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function resultEnteredBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'result_entered_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    /** @return Attribute<string, never> */
    protected function workflowStage(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status === LabRequestItemStatus::CANCELLED) {
                return 'cancelled';
            }

            $specimenStatus = $this->relationLoaded('specimen')
                ? $this->specimen?->status
                : $this->specimen()->value('status');

            if ($specimenStatus === LabSpecimenStatus::REJECTED->value || $specimenStatus === LabSpecimenStatus::REJECTED) {
                return 'rejected';
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

            $hasCollectedSample = $this->relationLoaded('specimen')
                ? $this->specimen?->collected_at !== null
                : $this->specimen()->whereNotNull('collected_at')->exists();

            if ($hasCollectedSample || $this->received_at !== null) {
                return 'sample_collected';
            }

            return 'pending';
        });
    }

    /** @return Attribute<bool, never> */
    protected function resultVisible(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->approved_at !== null && $this->status === LabRequestItemStatus::COMPLETED,
        );
    }
}
