<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreLabRequestItemConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consumable_name' => ['required', 'string', 'max:150'],
            'unit_label' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'used_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'unit_label' => $this->filled('unit_label') ? $this->input('unit_label') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
            'used_at' => $this->filled('used_at') ? $this->input('used_at') : null,
        ]);
    }
}
