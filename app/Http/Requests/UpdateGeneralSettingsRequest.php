<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'require_payment_before_consultation' => ['required', 'boolean'],
            'require_payment_before_laboratory' => ['required', 'boolean'],
            'require_payment_before_pharmacy' => ['required', 'boolean'],
            'require_payment_before_procedures' => ['required', 'boolean'],
            'allow_insured_bypass_upfront_payment' => ['required', 'boolean'],
            'default_currency_id' => ['nullable', 'string', 'exists:currencies,id'],
            'patient_number_prefix' => ['nullable', 'string', 'max:20'],
            'visit_number_prefix' => ['nullable', 'string', 'max:20'],
            'receipt_number_prefix' => ['nullable', 'string', 'max:20'],
            'lab_request_prefix' => ['nullable', 'string', 'max:20'],
            'enable_batch_tracking_when_dispensing' => ['required', 'boolean'],
            'allow_partial_dispense' => ['required', 'boolean'],
            'require_review_before_lab_release' => ['required', 'boolean'],
            'require_approval_before_lab_release' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'require_payment_before_consultation',
            'require_payment_before_laboratory',
            'require_payment_before_pharmacy',
            'require_payment_before_procedures',
            'allow_insured_bypass_upfront_payment',
            'enable_batch_tracking_when_dispensing',
            'allow_partial_dispense',
            'require_review_before_lab_release',
            'require_approval_before_lab_release',
        ] as $booleanField) {
            $this->merge([
                $booleanField => filter_var($this->input($booleanField), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                    ?? false,
            ]);
        }
    }
}
