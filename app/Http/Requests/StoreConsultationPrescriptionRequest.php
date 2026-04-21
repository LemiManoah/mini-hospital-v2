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

     * @return array<string, mixed>

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


