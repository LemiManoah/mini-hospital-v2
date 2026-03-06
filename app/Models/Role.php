<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Role extends SpatieRole
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
