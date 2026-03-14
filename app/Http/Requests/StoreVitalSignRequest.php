<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'temperature' => ['nullable', 'numeric', 'min:25', 'max:45'],
            'temperature_unit' => ['required', 'in:celsius,fahrenheit'],
            'pulse_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:120'],
            'systolic_bp' => ['nullable', 'integer', 'min:0', 'max:350'],
            'diastolic_bp' => ['nullable', 'integer', 'min:0', 'max:250'],
            'oxygen_saturation' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'on_supplemental_oxygen' => ['nullable', 'boolean'],
            'oxygen_delivery_method' => ['nullable', 'string', 'max:50'],
            'oxygen_flow_rate' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'blood_glucose' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'blood_glucose_unit' => ['required', 'in:mg_dl,mmol_l'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'head_circumference_cm' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'chest_circumference_cm' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'muac_cm' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'capillary_refill' => ['nullable', 'string', 'max:20'],
        ];
    }
}
