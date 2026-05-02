<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBillingDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'patient_number' => ['required', 'string', 'max:50'],
            'visit_number' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
