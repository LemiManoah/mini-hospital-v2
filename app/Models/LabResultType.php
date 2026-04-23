<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabResultType extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'result_types';

    protected $casts = [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<LabTestCatalog, $this>
     */
    public function labTests(): HasMany
    {
        return $this->hasMany(LabTestCatalog::class, 'result_type_id');
    }
}
