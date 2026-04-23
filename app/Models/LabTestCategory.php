<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabTestCategory extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<LabTestCatalog, $this>
     */
    public function labTests(): HasMany
    {
        return $this->hasMany(LabTestCatalog::class, 'lab_test_category_id');
    }
}
