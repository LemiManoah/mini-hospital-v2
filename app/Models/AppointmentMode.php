<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class AppointmentMode extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\AppointmentModeFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_virtual',
        'is_active',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'is_virtual' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
