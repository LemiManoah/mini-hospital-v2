<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateChargeMasterPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'unit_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    /**
     * @return array{unit_price: int|float|string, is_active: bool, effective_from: string|null, effective_to: string|null}
     */
    public function priceData(): array
    {
        $validated = $this->validated();
        $unitPrice = $validated['unit_price'] ?? 0;

        return [
            'unit_price' => is_int($unitPrice) || is_float($unitPrice) || is_string($unitPrice) ? $unitPrice : 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'effective_from' => is_string($validated['effective_from'] ?? null) ? $validated['effective_from'] : null,
            'effective_to' => is_string($validated['effective_to'] ?? null) ? $validated['effective_to'] : null,
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'effective_from' => $this->input('effective_from') ?: null,
            'effective_to' => $this->input('effective_to') ?: null,
        ]);
    }
}
