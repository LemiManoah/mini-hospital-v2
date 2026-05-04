<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Unit;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

final class InventoryItemImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private int $importedCount = 0;

    private int $rowNumber = 2;

    /**
     * @var array<string, true>
     */
    private array $seenKeys = [];

    /**
     * @var list<array{row: int, name: string, messages: list<string>}>
     */
    private array $errors = [];

    public function __construct(
        private readonly InventoryItemType $itemType,
        private readonly string $tenantId,
        private readonly string $userId,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $prepared = $this->prepareRow($row);
            $validator = Validator::make(
                $prepared,
                $this->rules($prepared),
                $this->messages(),
            );

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $this->rowNumber,
                    'name' => $this->rowName($prepared),
                    'messages' => array_values($validator->errors()->all()),
                ];
                $this->rowNumber++;

                continue;
            }

            InventoryItem::query()->create($this->attributes($prepared));
            $this->importedCount++;
            $this->rowNumber++;
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * @return list<array{row: int, name: string, messages: list<string>}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function prepareRow(array|Collection $row): array
    {
        $row = $row instanceof Collection ? $row->all() : $row;

        return [
            ...$row,
            'name' => $this->str($row['name'] ?? null),
            'generic_name' => $this->str($row['generic_name'] ?? null),
            'brand_name' => $this->str($row['brand_name'] ?? null),
            'category' => $this->lower($row['category'] ?? null),
            'strength' => $this->str($row['strength'] ?? null),
            'dosage_form' => $this->lower($row['dosage_form'] ?? null),
            'unit' => $this->str($row['unit'] ?? null),
            'manufacturer' => $this->str($row['manufacturer'] ?? null),
            'expires' => $this->bool($row['expires'] ?? null),
            'is_controlled' => $this->bool($row['is_controlled'] ?? null),
            'schedule_class' => $this->str($row['schedule_class'] ?? null),
            'therapeutic_classes' => $this->str($row['therapeutic_classes'] ?? null),
            'description' => $this->str($row['description'] ?? null),
            'is_active' => $this->bool($row['is_active'] ?? true, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rules(array $row): array
    {
        $rules = [
            'unit' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->str($value) !== null && $this->resolveUnitId($value) === null) {
                        $fail('The unit must match an existing unit name or symbol.');
                    }
                },
            ],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'gte:minimum_stock_level'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'default_selling_price' => ['nullable', 'numeric', 'min:0'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'expires' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($this->itemType === InventoryItemType::DRUG) {
            return [
                ...$rules,
                'generic_name' => ['required', 'string', 'max:200', $this->duplicateRule($row)],
                'brand_name' => ['nullable', 'string', 'max:200'],
                'category' => ['required', new Enum(DrugCategory::class)],
                'strength' => ['required', 'string', 'max:50'],
                'dosage_form' => ['required', new Enum(DrugDosageForm::class)],
                'is_controlled' => ['nullable', 'boolean'],
                'schedule_class' => ['nullable', 'string', 'max:10'],
                'therapeutic_classes' => ['nullable', 'string'],
            ];
        }

        return [
            ...$rules,
            'name' => ['required', 'string', 'max:200', $this->duplicateRule($row)],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'category.Illuminate\Validation\Rules\Enum' => 'Category must be one of: analgesic, antibiotic, antipyretic, anti_malarial, antihypertensive, gastrointestinal, respiratory, other.',
            'dosage_form.Illuminate\Validation\Rules\Enum' => 'Dosage form must be one of: tablet, capsule, syrup, suspension, injection, infusion, cream, ointment, drops, inhaler, suppository, other.',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function duplicateRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            $key = $this->duplicateKey($row);

            if ($key === null) {
                return;
            }

            if (array_key_exists($key, $this->seenKeys)) {
                $fail('This item appears more than once in the uploaded file.');

                return;
            }

            $this->seenKeys[$key] = true;

            if ($this->exists($row, $value)) {
                $fail('This item already exists in the inventory catalog.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function exists(array $row, mixed $value): bool
    {
        if ($this->itemType === InventoryItemType::DRUG) {
            return InventoryItem::query()
                ->where('tenant_id', $this->tenantId)
                ->where('item_type', InventoryItemType::DRUG->value)
                ->where('generic_name', $this->str($row['generic_name'] ?? null))
                ->where('strength', $this->str($row['strength'] ?? null))
                ->where('dosage_form', $this->lower($row['dosage_form'] ?? null))
                ->exists();
        }

        return InventoryItem::query()
            ->where('tenant_id', $this->tenantId)
            ->where('item_type', InventoryItemType::CONSUMABLE->value)
            ->where('name', $this->str($value))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function duplicateKey(array $row): ?string
    {
        if ($this->itemType === InventoryItemType::DRUG) {
            $genericName = $this->lower($row['generic_name'] ?? null);
            $strength = $this->lower($row['strength'] ?? null);
            $dosageForm = $this->lower($row['dosage_form'] ?? null);

            if ($genericName === null || $strength === null || $dosageForm === null) {
                return null;
            }

            return implode('|', [$this->itemType->value, $genericName, $strength, $dosageForm]);
        }

        $name = $this->lower($row['name'] ?? null);

        return $name !== null ? implode('|', [$this->itemType->value, $name]) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function attributes(array $row): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'item_type' => $this->itemType->value,
            'name' => $this->itemType === InventoryItemType::DRUG
                ? $this->str($row['generic_name'] ?? null)
                : $this->str($row['name'] ?? null),
            'generic_name' => $this->itemType === InventoryItemType::DRUG ? $this->str($row['generic_name'] ?? null) : null,
            'brand_name' => $this->itemType === InventoryItemType::DRUG ? $this->str($row['brand_name'] ?? null) : null,
            'category' => $this->itemType === InventoryItemType::DRUG ? $this->lower($row['category'] ?? null) : null,
            'strength' => $this->itemType === InventoryItemType::DRUG ? $this->str($row['strength'] ?? null) : null,
            'dosage_form' => $this->itemType === InventoryItemType::DRUG ? $this->lower($row['dosage_form'] ?? null) : null,
            'unit_id' => $this->resolveUnitId($row['unit'] ?? null),
            'minimum_stock_level' => $this->number($row['minimum_stock_level'] ?? null, 0),
            'reorder_level' => $this->number($row['reorder_level'] ?? null, 0),
            'default_purchase_price' => $this->nullableNumber($row['default_purchase_price'] ?? null),
            'default_selling_price' => $this->nullableNumber($row['default_selling_price'] ?? null),
            'manufacturer' => $this->str($row['manufacturer'] ?? null),
            'expires' => $this->bool($row['expires'] ?? null),
            'is_controlled' => $this->itemType === InventoryItemType::DRUG && $this->bool($row['is_controlled'] ?? null),
            'schedule_class' => $this->itemType === InventoryItemType::DRUG ? $this->str($row['schedule_class'] ?? null) : null,
            'therapeutic_classes' => $this->itemType === InventoryItemType::DRUG ? $this->classes($row['therapeutic_classes'] ?? null) : null,
            'description' => $this->str($row['description'] ?? null),
            'is_active' => $this->bool($row['is_active'] ?? true, true),
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowName(array $row): string
    {
        $name = $this->str($row['generic_name'] ?? $row['name'] ?? null) ?? '';
        $strength = $this->str($row['strength'] ?? null) ?? '';

        if ($name !== '' && $strength !== '') {
            return sprintf('%s %s', $name, $strength);
        }

        return $name !== '' ? $name : 'Row '.$this->rowNumber;
    }

    private function resolveUnitId(mixed $value): ?string
    {
        $unit = $this->str($value);

        if ($unit === null) {
            return null;
        }

        /** @var Unit|null $model */
        $model = Unit::query()
            ->where(function ($query) use ($unit): void {
                $query
                    ->where('symbol', $unit)
                    ->orWhere('name', $unit);
            })
            ->where(function ($query): void {
                $query
                    ->where('tenant_id', $this->tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->first();

        return $model?->id;
    }

    private function str(mixed $value): ?string
    {
        $trimmed = is_scalar($value) ? mb_trim((string) $value) : '';

        return $trimmed !== '' ? $trimmed : null;
    }

    private function lower(mixed $value): ?string
    {
        $trimmed = is_scalar($value) ? mb_strtolower(mb_trim((string) $value)) : '';

        return $trimmed !== '' ? $trimmed : null;
    }

    private function number(mixed $value, int|float $default): int|float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_numeric($value) ? (float) $value : $default;
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function bool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @return list<string>|null
     */
    private function classes(mixed $value): ?array
    {
        $classes = collect(explode(',', is_scalar($value) ? (string) $value : ''))
            ->map(static fn (string $class): string => mb_trim($class))
            ->filter()
            ->values()
            ->all();

        return $classes === [] ? null : array_values($classes);
    }
}
