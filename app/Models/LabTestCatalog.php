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
        'available_specimens',
        'specimen_type',
        'specimen_type_ids',
        'result_capture_type',
        'result_type_name',
    ];

    /** @return BelongsTo<LabTestCategory, $this> */
    public function labCategory(): BelongsTo
    {
        return $this->belongsTo(LabTestCategory::class, 'lab_test_category_id');
    }

    /** @return BelongsToMany<SpecimenType, $this> */
    public function specimenTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            SpecimenType::class,
            'lab_test_catalog_specimen_type',
            'lab_test_catalog_id',
            'specimen_type_id',
        )->withTimestamps();
    }

    /** @return BelongsTo<LabResultType, $this> */
    public function resultTypeDefinition(): BelongsTo
    {
        return $this->belongsTo(LabResultType::class, 'result_type_id');
    }

    /** @return HasMany<LabRequestItem, $this> */
    public function requestItems(): HasMany
    {
        return $this->hasMany(LabRequestItem::class, 'test_id');
    }

    /** @return HasMany<LabTestResultOption, $this> */
    public function resultOptions(): HasMany
    {
        return $this->hasMany(LabTestResultOption::class, 'lab_test_catalog_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /** @return HasMany<LabTestResultParameter, $this> */
    public function resultParameters(): HasMany
    {
        return $this->hasMany(LabTestResultParameter::class, 'lab_test_catalog_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /** @return Attribute<string|null, never> */
    protected function category(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->relationLoaded('labCategory')
                ? ($this->labCategory !== null ? (string) $this->labCategory->name : null)
                : (is_string($v = $this->labCategory()->value('name')) ? $v : null)
        );
    }

    /** @return Attribute<non-falsy-string|null, never> */
    protected function specimenType(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                /** @var array<int, string> $specimenNames */
                $specimenNames = $this->relationLoaded('specimenTypes')
                    ? $this->specimenTypes->pluck('name')->filter()->values()->all()
                    : $this->specimenTypes()->pluck('specimen_types.name')->filter()->values()->all();

                if ($specimenNames === []) {
                    return null;
                }

                return (string) implode(', ', $specimenNames);
            }
        );
    }

    /** @return Attribute<array<int, array{id: string, label: string}>, never> */
    protected function availableSpecimens(): Attribute
    {
        return Attribute::get(
            fn (): array => ($this->relationLoaded('specimenTypes')
                ? $this->specimenTypes
                : $this->specimenTypes()->get(['specimen_types.id', 'specimen_types.name']))
                ->map(static fn (SpecimenType $specimenType): array => [
                    'id' => (string) $specimenType->id,
                    'label' => (string) $specimenType->name,
                ])
                ->values()
                ->all()
        );
    }

    /** @return Attribute<array<int, string>, never> */
    protected function specimenTypeIds(): Attribute
    {
        return Attribute::get(
            fn (): array => $this->relationLoaded('specimenTypes')
                ? $this->specimenTypes->pluck('id')->map(fn (mixed $v): string => is_string($v) ? $v : '')->values()->all()
                : $this->specimenTypes()->pluck('specimen_types.id')->map(fn (mixed $v): string => is_string($v) ? $v : '')->values()->all()
        );
    }

    /** @return Attribute<string|null, never> */
    protected function resultCaptureType(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->relationLoaded('resultTypeDefinition')
                ? ($this->resultTypeDefinition !== null ? (string) $this->resultTypeDefinition->code : null)
                : (is_string($v = $this->resultTypeDefinition()->value('code')) ? $v : null)
        );
    }

    /** @return Attribute<string|null, never> */
    protected function resultTypeName(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->relationLoaded('resultTypeDefinition')
                ? ($this->resultTypeDefinition !== null ? (string) $this->resultTypeDefinition->name : null)
                : (is_string($v = $this->resultTypeDefinition()->value('name')) ? $v : null)
        );
    }
}
