<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Patient\CreateVisitPaymentDTO;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'payment_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $paymentMethodId = $this->input('payment_method_id');
            if (! is_string($paymentMethodId) || $paymentMethodId === '') {
                return;
            }

            $paymentMethod = PaymentMethod::query()->find($paymentMethodId);
            if (! $paymentMethod instanceof PaymentMethod || ! $paymentMethod->is_active) {
                $validator->errors()->add('payment_method_id', 'Please select an active payment method.');

                return;
            }

            if ($paymentMethod->requires_reference && ! $this->filled('reference_number')) {
                $validator->errors()->add('reference_number', sprintf('%s requires a reference number.', $paymentMethod->name));
            }
        });
    }

    public function createDto(): CreateVisitPaymentDTO
    {
        return CreateVisitPaymentDTO::fromRequest($this);
    }
}
