<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'temperature' => ['nullable', 'numeric'],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $temperature = $this->input('temperature');

                if ($temperature === null || $temperature === '') {
                    return;
                }

                $unit = $this->input('temperature_unit', 'celsius');
                [$min, $max] = $unit === 'fahrenheit'
                    ? [77, 113]
                    : [25, 45];

                if ((float) $temperature < $min || (float) $temperature > $max) {
                    $validator->errors()->add(
                        'temperature',
                        $unit === 'fahrenheit'
                            ? 'Temperature must be between 77 and 113 F.'
                            : 'Temperature must be between 25 and 45 C.'
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'on_supplemental_oxygen' => $this->boolean('on_supplemental_oxygen'),
            'temperature_unit' => $this->input('temperature_unit') ?: 'celsius',
            'blood_glucose_unit' => $this->input('blood_glucose_unit') ?: 'mg_dl',
        ]);
    }
}
