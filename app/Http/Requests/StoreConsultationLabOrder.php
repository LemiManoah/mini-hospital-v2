<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateLabOrderDTO;
use App\Data\Clinical\UpdateLabOrderDTO;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsultationLabOrder extends FormRequest
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

    public function createDto(): CreateLabOrderDTO
    {
        return CreateLabOrderDTO::fromRequest($this);
    }

    public function updateDto(): UpdateLabOrderDTO
    {
        return UpdateLabOrderDTO::fromRequest($this);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'priority' => $this->input('priority', Priority::ROUTINE->value),
            'is_stat' => $this->boolean('is_stat'),
        ]);
    }
}
