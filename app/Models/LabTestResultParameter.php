<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LabTestResultParameter extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'lab_test_catalog_id' => 'string',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'age_min' => 'integer',
        'age_max' => 'integer',
    ];

    /**
     * @return BelongsTo<LabTestCatalog, $this>
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'lab_test_catalog_id');
    }
}
