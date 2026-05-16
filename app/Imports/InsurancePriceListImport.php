<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Models\ChargeMaster;
use App\Models\InsurancePolicyItem;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

final class InsurancePriceListImport implements ToCollection, WithChunkReading, WithHeadingRow, WithLimit
{
    private const int MAX_ROWS = 1000;

    private int $importedCount = 0;

    private int $rowNumber = 2;

    /**
     * @var array<string, true>
     */
    private array $seenPriceKeys = [];

    /**
     * @var list<array{row: int, name: string, messages: list<string>}>
     */
    private array $errors = [];

    /**
     * @var list<array{row: int, name: string, branch: string, itemType: string, price: float, copayType: string, copayValue: float, effectiveFrom: string}>
     */
    private array $previewRows = [];

    public function __construct(
        private readonly string $tenantId,
        private readonly string $insurancePolicyId,
        private readonly string $userId,
        private readonly BillableItemType $itemType,
        private readonly string $branchName,
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

            $this->preview ? $this->previewPrice($prepared) : $this->importPrice($prepared);
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
     * @return list<array{row: int, name: string, branch: string, itemType: string, price: float, copayType: string, copayValue: float, effectiveFrom: string}>
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
            'charge_master_id' => $this->str($row['charge_master_id'] ?? null),
            'charge_master_code' => $this->str($row['charge_master_code'] ?? null),
            'charge_master_description' => $this->str($row['charge_master_description'] ?? null),
            'price' => $this->nullableNumber($row['price'] ?? null),
            'copay_type' => $this->lower($row['copay_type'] ?? InsuranceCopayType::NONE->value),
            'copay_value' => $this->nullableNumber($row['copay_value'] ?? 0),
            'effective_from' => $this->date($row['effective_from'] ?? null),
            'effective_to' => $this->date($row['effective_to'] ?? null),
            'status' => $this->lower($row['status'] ?? GeneralStatus::ACTIVE->value),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rules(array $row): array
    {
        return [
            'charge_master_id' => ['nullable', 'string', 'max:36', $this->resolvesChargeMasterRule($row)],
            'charge_master_code' => ['required_without_all:charge_master_id,charge_master_description', 'nullable', 'string', 'max:100', $this->resolvesChargeMasterRule($row)],
            'charge_master_description' => ['required_without_all:charge_master_id,charge_master_code', 'nullable', 'string', 'max:255', $this->resolvesChargeMasterRule($row)],
            'price' => ['required', 'numeric', 'min:0'],
            'copay_type' => ['required', new Enum(InsuranceCopayType::class)],
            'copay_value' => ['required', 'numeric', 'min:0', $this->copayValueRule($row)],
            'effective_from' => ['required', 'date', $this->duplicatePriceRule($row), $this->noOverlapRule($row)],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Status must be one of: active, inactive, suspended, cancelled, pending.',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function previewPrice(array $row): void
    {
        $this->previewRows[] = [
            'row' => $this->rowNumber,
            'name' => $this->rowName($row),
            'branch' => $this->branchName,
            'itemType' => $this->itemType->value,
            'price' => $this->nullableNumber($row['price'] ?? null) ?? 0.0,
            'copayType' => $this->lower($row['copay_type'] ?? null) ?? InsuranceCopayType::NONE->value,
            'copayValue' => $this->nullableNumber($row['copay_value'] ?? null) ?? 0.0,
            'effectiveFrom' => $this->date($row['effective_from'] ?? null) ?? '',
        ];

        $this->importedCount++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importPrice(array $row): void
    {
        $chargeMasterId = $this->resolveChargeMasterId($row);

        if ($chargeMasterId === null) {
            $this->recordError($row, ['This row could not be resolved during import.']);

            return;
        }

        InsurancePolicyItem::query()->create([
            'tenant_id' => $this->tenantId,
            'insurance_policy_id' => $this->insurancePolicyId,
            'charge_master_id' => $chargeMasterId,
            'price' => $this->nullableNumber($row['price'] ?? null) ?? 0,
            'copay_type' => $this->lower($row['copay_type'] ?? null) ?? InsuranceCopayType::NONE->value,
            'copay_value' => $this->nullableNumber($row['copay_value'] ?? null) ?? 0,
            'effective_from' => $this->date($row['effective_from'] ?? null),
            'effective_to' => $this->date($row['effective_to'] ?? null),
            'status' => $this->lower($row['status'] ?? null) ?? GeneralStatus::ACTIVE->value,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        $this->importedCount++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function copayValueRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            $copayValue = $this->nullableNumber($value);

            if (($this->lower($row['copay_type'] ?? null) ?? InsuranceCopayType::NONE->value) === InsuranceCopayType::PERCENTAGE->value
                && $copayValue !== null
                && $copayValue > 100
            ) {
                $fail('Percentage copay cannot be greater than 100.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolvesChargeMasterRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            if ($value === null || $value === '') {
                return;
            }

            if ($this->resolveChargeMasterId($row) === null) {
                $fail('The charge master item must match one active '.$this->itemType->value.' charge for this tenant.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function duplicatePriceRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            $key = $this->priceKey($row);

            if ($key === null) {
                return;
            }

            if (array_key_exists($key, $this->seenPriceKeys)) {
                $fail('This price row appears more than once in the uploaded file.');

                return;
            }

            $this->seenPriceKeys[$key] = true;
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function noOverlapRule(array $row): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($row): void {
            $chargeMasterId = $this->resolveChargeMasterId($row);
            $effectiveFrom = $this->date($row['effective_from'] ?? null);

            if ($chargeMasterId === null || $effectiveFrom === null) {
                return;
            }

            $effectiveTo = $this->date($row['effective_to'] ?? null);

            $exists = InsurancePolicyItem::query()
                ->where('tenant_id', $this->tenantId)
                ->where('insurance_policy_id', $this->insurancePolicyId)
                ->where('charge_master_id', $chargeMasterId)
                ->where('status', GeneralStatus::ACTIVE->value)
                ->where(function (Builder $query) use ($effectiveFrom, $effectiveTo): void {
                    $query
                        ->where(static fn (Builder $rangeQuery) => $rangeQuery->whereNull('effective_to')->orWhere('effective_to', '>=', $effectiveFrom))
                        ->where(function (Builder $rangeQuery) use ($effectiveTo): void {
                            if ($effectiveTo === null) {
                                return;
                            }

                            $rangeQuery->whereNull('effective_from')->orWhere('effective_from', '<=', $effectiveTo);
                        });
                })
                ->exists();

            if ($exists) {
                $fail('The selected effective date range overlaps an existing active policy price.');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveChargeMasterId(array $row): ?string
    {
        $id = $this->str($row['charge_master_id'] ?? null);
        $code = $this->str($row['charge_master_code'] ?? null);
        $description = $this->str($row['charge_master_description'] ?? null);

        if ($id === null && $code === null && $description === null) {
            return null;
        }

        $query = ChargeMaster::query()
            ->where('tenant_id', $this->tenantId)
            ->where('billable_type', $this->itemType->value)
            ->where('is_active', true)
            ->effectiveOn(now()->toDateString());

        if ($id !== null) {
            $query->whereKey($id);
        } else {
            $query->where(static fn (Builder $chargeQuery) => $chargeQuery
                ->when($code !== null, static fn (Builder $codeQuery) => $codeQuery->orWhere('item_code', $code))
                ->when($description !== null, static fn (Builder $descriptionQuery) => $descriptionQuery->orWhere('description', $description)));
        }

        $matches = $query->limit(2)->get(['id']);

        return $matches->count() === 1 ? (string) $matches->first()?->id : null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function priceKey(array $row): ?string
    {
        $chargeMasterId = $this->resolveChargeMasterId($row);
        $effectiveFrom = $this->date($row['effective_from'] ?? null);

        if ($chargeMasterId === null || $effectiveFrom === null) {
            return null;
        }

        return implode('|', [
            $this->insurancePolicyId,
            $chargeMasterId,
            $effectiveFrom,
        ]);
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
        return collect([
            $this->str($row['charge_master_code'] ?? null),
            $this->str($row['charge_master_description'] ?? null),
            $this->str($row['charge_master_id'] ?? null),
        ])->filter()->implode(' - ') ?: 'Row '.$this->rowNumber;
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

            return is_scalar($value) ? CarbonImmutable::parse((string) $value)->toDateString() : null;
        } catch (Throwable) {
            return $this->str($value);
        }
    }
}
