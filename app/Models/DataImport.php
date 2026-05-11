<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataImportStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Database\Factories\DataImportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DataImport extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<DataImportFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'user_id' => 'string',
        'status' => DataImportStatus::class,
        'imported_count' => 'integer',
        'skipped_count' => 'integer',
        'preview_count' => 'integer',
        'preview_rows' => 'array',
        'error_report' => 'array',
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
