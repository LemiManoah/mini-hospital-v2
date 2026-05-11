<?php

declare(strict_types=1);

namespace App\Imports;

use App\Actions\SyncFacilityServiceChargeMaster;
use App\Enums\FacilityServiceCategory;
use App\Models\FacilityService;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

final class FacilityServiceImport implements ToCollection, WithChunkReading, WithHeadingRow, WithLimit
{
    private const int MAX_ROWS = 1000;

    private int $importedCount = 0;

    private int $rowNumber = 2;

    /**
     * @var array<string, true>
     */
    private array $seenServiceCodes = [];

    /**
     * @var list<array{row: int, name: string, messages: list<string>}>
     */
    private array $errors = [];

    /**
     * @var list<array{row: int, name: string, serviceCode: string, category: string, sellingPrice: float|null}>
     */
    private array $previewRows = [];

    public function __construct(
        private readonly string $tenantId,
        private readonly string $userId,
        private readonly SyncFacilityServiceChargeMaster $syncFacilityServiceChargeMaster,
        private readonly bool $preview = false,
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
            $validator = Validator::make($prepared, $this->rules($prepared), $this->messages());

            if ($validator->fails()) {
                $this->recordError($prepared, array_values($validator->errors()->all()));
                $this->rowNumber++;

                continue;
            }

            $this->preview ? $this->previewService($prepared) : $this->importService($prepared);
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
     * @return list<array{row: int, name: string, serviceCode: string, category: string, sellingPrice: float|null}>
     */
    public function previewRows(): array
    {
        return $this->previewRows;
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
            'service_code' => $this->code($row['service_code'] ?? null),
            'name' => $this->str($row['name'] ?? null),
            'category' => $this->lower($row['category'] ?? FacilityServiceCategory::OTHER->value),
            'description' => $this->str($row['description'] ?? null),
            'cost_price' => $this->nullableNumber($row['cost_price'] ?? null),
            'selling_price' => $this->nullableNumber($row['selling_price'] ?? null),
            'is_billable' => $this->nullableBool($row['is_billable'] ?? true),
            'is_active' => $this->nullableBool($row['is_active'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rules(array $row): array
    {
        return [
            'service_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('facility_services', 'service_code')->where('tenant_id', $this->tenantId),
                $this->duplicateServiceCodeRule($row),
            ],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(FacilityServiceCategory::class)],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0', $this->billableSellingPriceRule($row)],
            'is_billable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'service_code.unique' => 'This service code already exists for this tenant.',
            'category.Illuminate\Validation\Rules\Enum' => 'Category must match one of the supported facility service categories.',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function previewService(array $row): void
    {
        $this->previewRows[] = [
            'row' => $this->rowNumber,
            'name' => $this->str($row['name'] ?? null) ?? 'Row '.$this->rowNumber,
            'serviceCode' => $this->code($row['service_code'] ?? null) ?? '',
            'category' => $this->lower($row['category'] ?? null) ?? '',
            'sellingPrice' => $this->nullableNumber($row['selling_price'] ?? null),
        ];

        $this->importedCount++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importService(array $row): void
    {
        $service = FacilityService::query()->create([
            'tenant_id' => $this->tenantId,
            'service_code' => $this->code($row['service_code'] ?? null),
            'name' => $this->str($row['name'] ?? null),
            'category' => $this->lower($row['category'] ?? null) ?? FacilityServiceCategory::OTHER->value,
            'description' => $this->str($row['description'] ?? null),
            'cost_price' => $this->nullableNumber($row['cost_price'] ?? null),
            'selling_price' => $this->nullableNumber($row['selling_price'] ?? null),
            'is_billable' => $this->nullableBool($row['is_billable'] ?? null) ?? true,
            'is_active' => $this->nullableBool($row['is_active'] ?? null) ?? true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        $this->syncFacilityServiceChargeMaster->handle($service);
        $this->importedCount++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function duplicateServiceCodeRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            $serviceCode = $this->code($row['service_code'] ?? null);

            if ($serviceCode === null) {
                return;
            }

            if (array_key_exists($serviceCode, $this->seenServiceCodes)) {
                $fail('This service code appears more than once in the uploaded file.');

                return;
            }

            $this->seenServiceCodes[$serviceCode] = true;
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function billableSellingPriceRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            if (($this->nullableBool($row['is_billable'] ?? null) ?? true) && $this->nullableNumber($value) === null) {
                $fail('Selling price is required for billable services.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $messages
     */
    private function recordError(array $row, array $messages): void
    {
        $this->errors[] = [
            'row' => $this->rowNumber,
            'name' => $this->str($row['name'] ?? null) ?? $this->code($row['service_code'] ?? null) ?? 'Row '.$this->rowNumber,
            'messages' => $messages,
        ];
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

    private function code(mixed $value): ?string
    {
        $trimmed = $this->str($value);

        return $trimmed !== null ? mb_strtoupper($trimmed) : null;
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

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (! is_scalar($value)) {
            return null;
        }

        return match (mb_strtolower(mb_trim($value))) {
            'true', 'yes', 'y', 'active', '1' => true,
            'false', 'no', 'n', 'inactive', '0' => false,
            default => null,
        };
    }
}
