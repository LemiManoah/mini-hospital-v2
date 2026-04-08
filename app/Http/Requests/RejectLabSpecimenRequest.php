<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectLabSpecimenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string'],
            'redirect_to' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rejection_reason' => $this->filled('rejection_reason') ? $this->input('rejection_reason') : null,
            'redirect_to' => $this->filled('redirect_to') ? $this->input('redirect_to') : null,
        ]);
    }
}
