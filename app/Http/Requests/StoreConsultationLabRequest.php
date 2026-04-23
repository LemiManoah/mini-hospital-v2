<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateLabRequestDTO;
use App\Data\Clinical\UpdateLabRequestDTO;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsultationLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('lab_test_catalogs', 'id')->where('is_active', true),
            ],
            'clinical_notes' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'diagnosis_code' => ['nullable', 'string', 'max:10'],
            'is_stat' => ['nullable', 'boolean'],
        ];
    }

    public function createDto(): CreateLabRequestDTO
    {
        return CreateLabRequestDTO::fromRequest($this);
    }

    public function updateDto(): UpdateLabRequestDTO
    {
        return UpdateLabRequestDTO::fromRequest($this);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'priority' => $this->input('priority', Priority::ROUTINE->value),
            'is_stat' => $this->boolean('is_stat'),
        ]);
    }
}
