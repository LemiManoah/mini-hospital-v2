<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Patient\CreateVisitPaymentDTO;
use Illuminate\Foundation\Http\FormRequest;

final class StoreVisitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function createDto(): CreateVisitPaymentDTO
    {
        return CreateVisitPaymentDTO::fromRequest($this);
    }
}
