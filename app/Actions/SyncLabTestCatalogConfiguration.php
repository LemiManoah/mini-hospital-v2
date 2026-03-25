<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use Illuminate\Support\Arr;

final readonly class SyncLabTestCatalogConfiguration
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(LabTestCatalog $labTestCatalog, array $attributes): void
    {
        $labTestCatalog->specimenTypes()->sync($this->normalizedSpecimenTypeIds($attributes['specimen_type_ids'] ?? []));

        $resultTypeCode = LabResultType::query()
            ->whereKey($labTestCatalog->result_type_id)
            ->value('code');

        if ($resultTypeCode === 'defined_option') {
            $labTestCatalog->resultParameters()->delete();
            $this->syncResultOptions($labTestCatalog, $attributes['result_options'] ?? []);

            return;
        }

        if ($resultTypeCode === 'parameter_panel') {
            $labTestCatalog->resultOptions()->delete();
            $this->syncResultParameters($labTestCatalog, $attributes['result_parameters'] ?? []);

            return;
        }

        $labTestCatalog->resultOptions()->delete();
        $labTestCatalog->resultParameters()->delete();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function normalizedSpecimenTypeIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => is_string($item) && $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return array<int, array{label: string, sort_order: int, is_active: bool}>
     */
    private function normalizedResultOptions(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(static function (array $item, int $index): ?array {
                $label = mb_trim((string) Arr::get($item, 'label', ''));

                if ($label === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return array<int, array{label: string, unit: ?string, reference_range: ?string, value_type: string, sort_order: int, is_active: bool}>
     */
    private function normalizedResultParameters(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(static function (array $item, int $index): ?array {
                $label = mb_trim((string) Arr::get($item, 'label', ''));

                if ($label === '') {
                    return null;
                }

                $unit = mb_trim((string) Arr::get($item, 'unit', ''));
                $referenceRange = mb_trim((string) Arr::get($item, 'reference_range', ''));
                $valueType = mb_trim((string) Arr::get($item, 'value_type', 'numeric'));

                if (! in_array($valueType, ['numeric', 'text'], true)) {
                    $valueType = 'numeric';
                }

                return [
                    'label' => $label,
                    'unit' => $unit === '' ? null : $unit,
                    'reference_range' => $referenceRange === '' ? null : $referenceRange,
                    'value_type' => $valueType,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     */
    private function syncResultOptions(LabTestCatalog $labTestCatalog, mixed $value): void
    {
        $labTestCatalog->resultOptions()->delete();

        $payload = $this->normalizedResultOptions($value);

        if ($payload === []) {
            return;
        }

        $labTestCatalog->resultOptions()->createMany($payload);
    }

    /**
     * @param  mixed  $value
     */
    private function syncResultParameters(LabTestCatalog $labTestCatalog, mixed $value): void
    {
        $labTestCatalog->resultParameters()->delete();

        $payload = $this->normalizedResultParameters($value);

        if ($payload === []) {
            return;
        }

        $labTestCatalog->resultParameters()->createMany($payload);
    }
}
