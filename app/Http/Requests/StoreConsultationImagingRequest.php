<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\PregnancyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsultationImagingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modality' => ['required', Rule::enum(ImagingModality::class)],
            'body_part' => ['required', 'string', 'max:100'],
            'laterality' => ['required', Rule::enum(ImagingLaterality::class)],
            'clinical_history' => ['required', 'string'],
            'indication' => ['required', 'string'],
            'priority' => ['required', Rule::enum(ImagingPriority::class)],
            'requires_contrast' => ['nullable', 'boolean'],
            'contrast_allergy_status' => ['nullable', 'string', 'max:50'],
            'pregnancy_status' => ['required', Rule::enum(PregnancyStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_contrast' => $this->boolean('requires_contrast'),
            'laterality' => $this->input('laterality') ?: 'na',
            'pregnancy_status' => $this->input('pregnancy_status') ?: 'unknown',
        ]);
    }
}
