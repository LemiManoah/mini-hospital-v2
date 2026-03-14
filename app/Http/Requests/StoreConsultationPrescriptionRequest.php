<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreConsultationPrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_diagnosis' => ['nullable', 'string', 'max:255'],
            'pharmacy_notes' => ['nullable', 'string'],
            'is_discharge_medication' => ['nullable', 'boolean'],
            'is_long_term' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => [
                'required',
                'string',
                Rule::exists('drugs', 'id')->where('is_active', true),
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
            foreach ($this->input('items', []) as $index => $item) {
                $isPrn = filter_var($item['is_prn'] ?? false, FILTER_VALIDATE_BOOL);
                $prnReason = mb_trim((string) ($item['prn_reason'] ?? ''));

                if ($isPrn && $prnReason === '') {
                    $validator->errors()->add(sprintf('items.%d.prn_reason', $index), 'PRN reason is required when prescribing as needed.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(static function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                $item['is_prn'] = filter_var($item['is_prn'] ?? false, FILTER_VALIDATE_BOOL);
                $item['is_external_pharmacy'] = filter_var($item['is_external_pharmacy'] ?? false, FILTER_VALIDATE_BOOL);

                return $item;
            })
            ->values()
            ->all();

        $this->merge([
            'is_discharge_medication' => $this->boolean('is_discharge_medication'),
            'is_long_term' => $this->boolean('is_long_term'),
            'items' => $items,
        ]);
    }
}
