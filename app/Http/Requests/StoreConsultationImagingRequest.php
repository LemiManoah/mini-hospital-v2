<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateImagingRequestDTO;
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

    public function createDto(): CreateImagingRequestDTO
    {
        /** @var array{
         *   modality: string,
         *   body_part: string,
         *   laterality: string,
         *   clinical_history: string,
         *   indication: string,
         *   priority: string,
         *   requires_contrast?: bool,
         *   contrast_allergy_status?: string|null,
         *   pregnancy_status: string
         * } $validated
         */
        $validated = $this->validated();

        return CreateImagingRequestDTO::fromRequest($validated);
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
