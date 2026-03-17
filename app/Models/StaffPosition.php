<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StaffPosition extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\StaffPositionFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<Staff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
