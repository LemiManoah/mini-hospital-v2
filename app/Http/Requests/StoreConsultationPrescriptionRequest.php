<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InventoryItemType;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreConsultationPrescriptionRequest extends FormRequest
{
    /**
     * @return list<array{
     *   inventory_item_id?: string|null,
     *   dosage?: string|null,
     *   frequency?: string|null,
     *   route?: string|null,
     *   duration_days?: int|null,
     *   quantity?: int|null,
     *   instructions?: string|null,
     *   is_prn?: bool,
     *   prn_reason?: string|null,
     *   is_external_pharmacy?: bool
     * }>
     */
    private function prescriptionItems(): array
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return [];
        }

        $normalizedItems = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalizedItems[] = [
                'inventory_item_id' => is_string($item['inventory_item_id'] ?? null) ? $item['inventory_item_id'] : null,
                'dosage' => is_string($item['dosage'] ?? null) ? $item['dosage'] : null,
                'frequency' => is_string($item['frequency'] ?? null) ? $item['frequency'] : null,
                'route' => is_string($item['route'] ?? null) ? $item['route'] : null,
                'duration_days' => is_numeric($item['duration_days'] ?? null) ? (int) $item['duration_days'] : null,
                'quantity' => is_numeric($item['quantity'] ?? null) ? (int) $item['quantity'] : null,
                'instructions' => is_string($item['instructions'] ?? null) ? $item['instructions'] : null,
                'is_prn' => filter_var($item['is_prn'] ?? false, FILTER_VALIDATE_BOOL),
                'prn_reason' => is_string($item['prn_reason'] ?? null) ? $item['prn_reason'] : null,
                'is_external_pharmacy' => filter_var($item['is_external_pharmacy'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        }

        return $normalizedItems;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'primary_diagnosis' => ['nullable', 'string', 'max:255'],
            'pharmacy_notes' => ['nullable', 'string'],
            'is_discharge_medication' => ['nullable', 'boolean'],
            'is_long_term' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                Rule::exists('inventory_items', 'id')->where(static function (QueryBuilder $query): void {
                    $query
                        ->where('is_active', true)
                        ->where('item_type', InventoryItemType::DRUG->value)
                        ->whereNull('deleted_at');
                }),
            ],
            'items.*.dosage' => ['required', 'string', 'max:50'],
            'items.*.frequency' => ['required', 'string', 'max:50'],
            'items.*.route' => ['required', 'string', 'max:50'],
            'items.*.duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.instructions' => ['nullable', 'string'],
            'items.*.is_prn' => ['nullable', 'boolean'],
            'items.*.prn_reason' => ['nullable', 'string', 'max:100'],
            'items.*.is_external_pharmacy' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->prescriptionItems() as $index => $item) {
                $isPrn = $item['is_prn'] ?? false;
                $prnReason = mb_trim((string) ($item['prn_reason'] ?? ''));

                if ($isPrn && $prnReason === '') {
                    $validator->errors()->add(sprintf('items.%d.prn_reason', $index), 'PRN reason is required when prescribing as needed.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_discharge_medication' => $this->boolean('is_discharge_medication'),
            'is_long_term' => $this->boolean('is_long_term'),
            'items' => $this->prescriptionItems(),
        ]);
    }
}
