<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Inventory\CreateInventoryItemDTO;
use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isDrug = $this->input('item_type') === InventoryItemType::DRUG->value;

        return [
            'item_type' => ['required', Rule::enum(InventoryItemType::class)],
            'name' => [Rule::requiredIf(! $isDrug), 'nullable', 'string', 'max:200'],
            'generic_name' => [Rule::requiredIf($isDrug), 'nullable', 'string', 'max:200'],
            'brand_name' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'unit_id' => [
                'nullable',
                'string',
                Rule::exists('units', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'category' => [Rule::requiredIf($isDrug), 'nullable', Rule::enum(DrugCategory::class)],
            'strength' => [Rule::requiredIf($isDrug), 'nullable', 'string', 'max:50'],
            'dosage_form' => [Rule::requiredIf($isDrug), 'nullable', Rule::enum(DrugDosageForm::class)],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'gte:minimum_stock_level'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'default_selling_price' => ['nullable', 'numeric', 'min:0'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'expires' => ['nullable', 'boolean'],
            'is_controlled' => ['nullable', 'boolean'],
            'schedule_class' => ['nullable', 'string', 'max:10'],
            'therapeutic_classes' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'interactions' => ['nullable', 'string'],
            'side_effects' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function createDto(): CreateInventoryItemDTO
    {
        return CreateInventoryItemDTO::fromRequest($this);
    }

    protected function prepareForValidation(): void
    {
        $itemType = $this->input('item_type');
        $isDrug = $itemType === InventoryItemType::DRUG->value;
        $genericName = $this->normalizedText('generic_name');

        $this->merge([
            'name' => $isDrug
                ? $genericName
                : $this->normalizedText('name'),
            'generic_name' => $isDrug ? $genericName : null,
            'brand_name' => $isDrug ? $this->normalizedText('brand_name') : null,
            'description' => $this->normalizedText('description'),
            'unit_id' => $this->normalizedText('unit_id'),
            'strength' => $isDrug ? $this->normalizedText('strength') : null,
            'schedule_class' => $isDrug ? $this->normalizedText('schedule_class') : null,
            'manufacturer' => $this->normalizedText('manufacturer'),
            'contraindications' => $isDrug ? $this->normalizedText('contraindications') : null,
            'interactions' => $isDrug ? $this->normalizedText('interactions') : null,
            'side_effects' => $isDrug ? $this->normalizedText('side_effects') : null,
            'category' => $isDrug ? $this->input('category') : null,
            'dosage_form' => $isDrug ? $this->input('dosage_form') : null,
            'is_active' => $this->boolean('is_active', true),
            'expires' => $this->boolean('expires'),
            'is_controlled' => $isDrug && $this->boolean('is_controlled'),
            'minimum_stock_level' => $this->numericOrDefault('minimum_stock_level', 0),
            'reorder_level' => $this->numericOrDefault('reorder_level', 0),
            'default_purchase_price' => $this->nullableNumeric('default_purchase_price'),
            'default_selling_price' => $this->nullableNumeric('default_selling_price'),
            'therapeutic_classes' => $isDrug ? $this->therapeuticClasses() : null,
        ]);
    }

    private function normalizedText(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function numericOrDefault(string $key, int|float $default): int|float
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    private function nullableNumeric(string $key): int|float|string|null
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array<int, string>|null
     */
    private function therapeuticClasses(): ?array
    {
        $value = $this->input('therapeutic_classes');

        if (! is_string($value)) {
            return null;
        }

        $classes = collect(explode(',', $value))
            ->map(static fn (string $class): string => mb_trim($class))
            ->filter()
            ->values()
            ->all();

        return $classes === [] ? null : $classes;
    }
}
