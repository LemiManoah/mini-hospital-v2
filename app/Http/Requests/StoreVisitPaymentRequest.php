<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Patient\CreateVisitPaymentDTO;
use App\Models\FacilityBranch;
use App\Models\PaymentMethod;
use App\Support\BranchContext;
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
            'currency_id' => ['nullable', 'uuid', 'exists:currencies,id'],
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

            $currencyId = $this->input('currency_id');
            $branch = BranchContext::getActiveBranch();

            if ($currencyId === null || $currencyId === '') {
                return;
            }

            if (! $branch instanceof FacilityBranch || ! $branch->multi_currency_enabled) {
                $validator->errors()->add('currency_id', 'Multi-currency is not enabled for this branch.');

                return;
            }

            if (
                ! $branch->supportedCurrencies()
                    ->where('currencies.id', $currencyId)
                    ->exists()
            ) {
                $validator->errors()->add('currency_id', 'The selected currency is not enabled for this branch.');
            }
        });
    }

    public function createDto(): CreateVisitPaymentDTO
    {
        return CreateVisitPaymentDTO::fromRequest($this);
    }
}
