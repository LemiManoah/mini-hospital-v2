<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationItem;
use App\Models\StockMovement;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

final class InventoryItemImport implements ToCollection, WithChunkReading, WithHeadingRow, WithLimit
{
    private const int MAX_ROWS = 1000;

    private int $importedCount = 0;

    private int $rowNumber = 2;

    /**
     * @var array<string, true>
     */
    private array $seenOpeningBalanceKeys = [];

    /**
     * @var array<string, string|null>
     */
    private array $unitIdsByLookup = [];

    /**
     * @var array<string, string|null>
     */
    private array $locationIdsByLookup = [];

    /**
     * @var list<array{row: int, name: string, messages: list<string>}>
     */
    private array $errors = [];

    public function __construct(
        private readonly InventoryItemType $itemType,
        private readonly string $tenantId,
        private readonly string $branchId,
        private readonly string $userId,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if ($this->isBlankRow($row)) {
                $this->rowNumber++;

                continue;
            }

            $prepared = $this->prepareRow($row);
            $validator = Validator::make(
                $prepared,
                $this->rules($prepared),
                $this->messages(),
            );

            if ($validator->fails()) {
                $this->recordError($prepared, array_values($validator->errors()->all()));
                $this->rowNumber++;

                continue;
            }

            $this->importOpeningBalance($prepared);
            $this->rowNumber++;
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function limit(): int
    {
        return self::MAX_ROWS;
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
        $batchNumber = $this->str($row['batch_number'] ?? null);
        $expiryDate = $this->date($row['expiry_date'] ?? null);
        $expires = $this->bool($row['expires'] ?? null) || $expiryDate !== null;

        return [
            ...$row,
            'name' => $this->str($row['name'] ?? null),
            'generic_name' => $this->str($row['generic_name'] ?? null),
            'brand_name' => $this->str($row['brand_name'] ?? null),
            'category' => $this->lower($row['category'] ?? null),
            'strength' => $this->str($row['strength'] ?? null),
            'dosage_form' => $this->lower($row['dosage_form'] ?? null),
            'unit' => $this->str($row['unit'] ?? null),
            'inventory_location' => $this->str($row['inventory_location'] ?? null),
            'quantity_on_hand' => $this->nullableNumber($row['quantity_on_hand'] ?? null),
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'unit_cost' => $this->nullableNumber($row['unit_cost'] ?? null),
            'minimum_stock_level' => $this->nullableNumber($row['minimum_stock_level'] ?? null),
            'reorder_level' => $this->nullableNumber($row['reorder_level'] ?? null),
            'default_selling_price' => $this->nullableNumber($row['default_selling_price'] ?? null),
            'manufacturer' => $this->str($row['manufacturer'] ?? null),
            'expires' => $expires,
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
            'inventory_location' => [
                'required',
                'string',
                'max:150',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->resolveInventoryLocationId($value) === null) {
                        $fail('The inventory location must match an active location name or code for the current branch.');
                    }
                },
            ],
            'quantity_on_hand' => ['required', 'numeric', 'min:0.001', $this->duplicateOpeningBalanceRule($row)],
            'batch_number' => [$row['expires'] === true ? 'required' : 'nullable', 'string', 'max:100'],
            'expiry_date' => [$row['expires'] === true ? 'required' : 'nullable', 'date'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'gte:minimum_stock_level'],
            'default_selling_price' => ['nullable', 'numeric', 'min:0'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'expires' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($this->itemType === InventoryItemType::DRUG) {
            return [
                ...$rules,
                'generic_name' => ['required', 'string', 'max:200'],
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
            'name' => ['required', 'string', 'max:200'],
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
    private function duplicateOpeningBalanceRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            $key = $this->openingBalanceKey($row);

            if ($key === null) {
                return;
            }

            if (array_key_exists($key, $this->seenOpeningBalanceKeys)) {
                $fail('This opening stock row appears more than once in the uploaded file.');

                return;
            }

            $this->seenOpeningBalanceKeys[$key] = true;
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importOpeningBalance(array $row): void
    {
        DB::transaction(function () use ($row): void {
            $inventoryItem = $this->findOrCreateInventoryItem($row);
            $locationId = $this->resolveInventoryLocationId($row['inventory_location'] ?? null);

            if ($locationId === null) {
                $this->recordError($row, ['The inventory location could not be resolved.']);

                return;
            }

            if ($this->batchExists($inventoryItem, $locationId, $row)) {
                $this->recordError($row, ['This batch already has opening stock for the selected location.']);

                return;
            }

            InventoryLocationItem::query()->updateOrCreate(
                [
                    'inventory_location_id' => $locationId,
                    'inventory_item_id' => $inventoryItem->id,
                ],
                [
                    'tenant_id' => $this->tenantId,
                    'branch_id' => $this->branchId,
                    'minimum_stock_level' => $this->number($row['minimum_stock_level'] ?? null, 0),
                    'reorder_level' => $this->number($row['reorder_level'] ?? null, 0),
                    'default_selling_price' => $this->nullableNumber($row['default_selling_price'] ?? null),
                    'is_active' => $this->bool($row['is_active'] ?? true, true),
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ],
            );

            $batch = InventoryBatch::query()->create([
                'tenant_id' => $this->tenantId,
                'branch_id' => $this->branchId,
                'inventory_location_id' => $locationId,
                'inventory_item_id' => $inventoryItem->id,
                'goods_receipt_item_id' => null,
                'batch_number' => $this->str($row['batch_number'] ?? null),
                'expiry_date' => $this->date($row['expiry_date'] ?? null),
                'unit_cost' => $this->number($row['unit_cost'] ?? null, 0),
                'quantity_received' => $this->number($row['quantity_on_hand'] ?? null, 0),
                'received_at' => now(),
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            StockMovement::query()->create([
                'tenant_id' => $this->tenantId,
                'branch_id' => $this->branchId,
                'inventory_location_id' => $locationId,
                'inventory_item_id' => $inventoryItem->id,
                'inventory_batch_id' => $batch->id,
                'movement_type' => StockMovementType::OpeningBalance,
                'quantity' => $this->number($row['quantity_on_hand'] ?? null, 0),
                'unit_cost' => $this->number($row['unit_cost'] ?? null, 0),
                'notes' => 'Opening stock imported from data upload.',
                'occurred_at' => now(),
                'created_by' => $this->userId,
            ]);

            $this->importedCount++;
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function findOrCreateInventoryItem(array $row): InventoryItem
    {
        $attributes = $this->catalogLookup($row);
        $values = $this->catalogAttributes($row);

        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::query()->firstOrCreate($attributes, $values);

        if (! $inventoryItem->wasRecentlyCreated) {
            $updates = [
                'expires' => $inventoryItem->expires || $this->bool($row['expires'] ?? null),
                'updated_by' => $this->userId,
            ];

            if ($this->str($row['manufacturer'] ?? null) !== null) {
                $updates['manufacturer'] = $this->str($row['manufacturer'] ?? null);
            }

            if ($this->resolveUnitId($row['unit'] ?? null) !== null) {
                $updates['unit_id'] = $this->resolveUnitId($row['unit'] ?? null);
            }

            $inventoryItem->update($updates);
        }

        return $inventoryItem;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function catalogLookup(array $row): array
    {
        if ($this->itemType === InventoryItemType::DRUG) {
            return [
                'tenant_id' => $this->tenantId,
                'item_type' => InventoryItemType::DRUG->value,
                'generic_name' => $this->str($row['generic_name'] ?? null),
                'strength' => $this->str($row['strength'] ?? null),
                'dosage_form' => $this->lower($row['dosage_form'] ?? null),
            ];
        }

        return [
            'tenant_id' => $this->tenantId,
            'item_type' => InventoryItemType::CONSUMABLE->value,
            'name' => $this->str($row['name'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function catalogAttributes(array $row): array
    {
        return [
            'name' => $this->itemType === InventoryItemType::DRUG
                ? $this->str($row['generic_name'] ?? null)
                : $this->str($row['name'] ?? null),
            'brand_name' => $this->itemType === InventoryItemType::DRUG ? $this->str($row['brand_name'] ?? null) : null,
            'category' => $this->itemType === InventoryItemType::DRUG ? $this->lower($row['category'] ?? null) : null,
            'unit_id' => $this->resolveUnitId($row['unit'] ?? null),
            'minimum_stock_level' => $this->number($row['minimum_stock_level'] ?? null, 0),
            'reorder_level' => $this->number($row['reorder_level'] ?? null, 0),
            'default_purchase_price' => $this->nullableNumber($row['unit_cost'] ?? null),
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
    private function batchExists(InventoryItem $inventoryItem, string $locationId, array $row): bool
    {
        return InventoryBatch::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('inventory_location_id', $locationId)
            ->where('inventory_item_id', $inventoryItem->id)
            ->where('batch_number', $this->str($row['batch_number'] ?? null))
            ->where('expiry_date', $this->date($row['expiry_date'] ?? null))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function openingBalanceKey(array $row): ?string
    {
        $catalogKey = $this->catalogKey($row);
        $location = $this->lower($row['inventory_location'] ?? null);

        if ($catalogKey === null || $location === null) {
            return null;
        }

        return implode('|', [
            $catalogKey,
            $location,
            $this->lower($row['batch_number'] ?? null) ?? '',
            $this->date($row['expiry_date'] ?? null) ?? '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function catalogKey(array $row): ?string
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
     * @param  list<string>  $messages
     */
    private function recordError(array $row, array $messages): void
    {
        $this->errors[] = [
            'row' => $this->rowNumber,
            'name' => $this->rowName($row),
            'messages' => $messages,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowName(array $row): string
    {
        $name = $this->str($row['generic_name'] ?? $row['name'] ?? null) ?? '';
        $strength = $this->str($row['strength'] ?? null) ?? '';
        $batchNumber = $this->str($row['batch_number'] ?? null) ?? '';

        return collect([$name, $strength, $batchNumber])
            ->filter()
            ->implode(' ') ?: 'Row '.$this->rowNumber;
    }

    private function resolveUnitId(mixed $value): ?string
    {
        $unit = $this->str($value);

        if ($unit === null) {
            return null;
        }

        $lookup = mb_strtolower($unit);

        if (array_key_exists($lookup, $this->unitIdsByLookup)) {
            return $this->unitIdsByLookup[$lookup];
        }

        /** @var Unit|null $model */
        $model = Unit::query()
            ->where(function (Builder $query) use ($unit): void {
                $query
                    ->where('symbol', $unit)
                    ->orWhere('name', $unit);
            })
            ->where(function (Builder $query): void {
                $query
                    ->where('tenant_id', $this->tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->first();

        return $this->unitIdsByLookup[$lookup] = $model?->id;
    }

    private function resolveInventoryLocationId(mixed $value): ?string
    {
        $location = $this->str($value);

        if ($location === null) {
            return null;
        }

        $lookup = mb_strtolower($location);

        if (array_key_exists($lookup, $this->locationIdsByLookup)) {
            return $this->locationIdsByLookup[$lookup];
        }

        /** @var InventoryLocation|null $model */
        $model = InventoryLocation::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($location): void {
                $query
                    ->where('location_code', $location)
                    ->orWhere('name', $location);
            })
            ->first();

        return $this->locationIdsByLookup[$lookup] = $model?->id;
    }

    /**
     * @param  array<string, mixed>|Collection<string, mixed>  $row
     */
    private function isBlankRow(array|Collection $row): bool
    {
        $row = $row instanceof Collection ? $row->all() : $row;

        foreach ($row as $value) {
            if ($this->str($value) !== null) {
                return false;
            }
        }

        return true;
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

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return CarbonImmutable::instance($value)->toDateString();
            }

            if (is_numeric($value)) {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }

            if (! is_scalar($value)) {
                return null;
            }

            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return $this->str($value);
        }
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
