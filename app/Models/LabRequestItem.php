<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabRequestItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class LabRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\LabRequestItemFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    protected $fillable = [
        'request_id',
        'test_id',
        'status',
        'price',
        'is_external',
        'external_lab_name',
        'completed_at',
    ];

    #[Override]
    protected $casts = [
        'request_id' => 'string',
        'test_id' => 'string',
        'status' => LabRequestItemStatus::class,
        'price' => 'float',
        'is_external' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'request_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'test_id');
    }
}
