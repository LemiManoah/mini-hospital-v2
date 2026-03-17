<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Drug extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\DrugFactory> */
    use HasFactory;

    use HasUuids;
    protected $casts = [
        'tenant_id' => 'string',
        'category' => DrugCategory::class,
        'dosage_form' => DrugDosageForm::class,
        'is_controlled' => 'boolean',
        'therapeutic_classes' => 'array',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
