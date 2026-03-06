<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Permission extends SpatiePermission
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
