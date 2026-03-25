<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabTestCatalog extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\LabTestCatalogFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'lab_test_category_id' => 'string',
        'result_type_id' => 'string',
        'base_price' => 'float',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'category',
        'specimen_type',
        'specimen_type_ids',
        'result_capture_type',
        'result_type_name',
    ];

    public function labCategory(): BelongsTo
    {
        return $this->belongsTo(LabTestCategory::class, 'lab_test_category_id');
    }

    public function specimenTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            SpecimenType::class,
            'lab_test_catalog_specimen_type',
            'lab_test_catalog_id',
            'specimen_type_id',
        )->withTimestamps();
    }

    public function resultTypeDefinition(): BelongsTo
    {
        return $this->belongsTo(LabResultType::class, 'result_type_id');
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(LabRequestItem::class, 'test_id');
    }

    public function resultOptions(): HasMany
    {
        return $this->hasMany(LabTestResultOption::class, 'lab_test_catalog_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public function resultParameters(): HasMany
    {
        return $this->hasMany(LabTestResultParameter::class, 'lab_test_catalog_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    protected function category(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->relationLoaded('labCategory')
                ? $this->labCategory?->name
                : $this->labCategory()->value('name')
        );
    }

    protected function specimenType(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                $specimenNames = $this->relationLoaded('specimenTypes')
                    ? $this->specimenTypes->pluck('name')->filter()->values()->all()
                    : $this->specimenTypes()->pluck('specimen_types.name')->filter()->values()->all();

                if ($specimenNames === []) {
                    return null;
                }

                return implode(', ', $specimenNames);
            }
        );
    }

    protected function specimenTypeIds(): Attribute
    {
        return Attribute::get(
            fn (): array => $this->relationLoaded('specimenTypes')
                ? $this->specimenTypes->pluck('id')->filter()->values()->all()
                : $this->specimenTypes()->pluck('specimen_types.id')->filter()->values()->all()
        );
    }

    protected function resultCaptureType(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->relationLoaded('resultTypeDefinition')
                ? $this->resultTypeDefinition?->code
                : $this->resultTypeDefinition()->value('code')
        );
    }

    protected function resultTypeName(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->relationLoaded('resultTypeDefinition')
                ? $this->resultTypeDefinition?->name
                : $this->resultTypeDefinition()->value('name')
        );
    }
}
