<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDrugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generic_name' => ['required', 'string', 'max:200'],
            'brand_name' => ['nullable', 'string', 'max:200'],
            'drug_code' => ['required', 'string', 'max:50', Rule::unique('drugs', 'drug_code')],
            'category' => ['required', Rule::enum(DrugCategory::class)],
            'dosage_form' => ['required', Rule::enum(DrugDosageForm::class)],
            'strength' => ['required', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:20'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'is_controlled' => ['nullable', 'boolean'],
            'schedule_class' => ['nullable', 'string', 'max:10'],
            'therapeutic_classes' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'interactions' => ['nullable', 'string'],
            'side_effects' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_controlled' => $this->boolean('is_controlled'),
            'is_active' => $this->boolean('is_active', true),
            'therapeutic_classes' => $this->therapeuticClasses(),
        ]);
    }

    /**
     * @return array<int, string>|null
     */
    private function therapeuticClasses(): ?array
    {
        $value = $this->input('therapeutic_classes');

        if (! is_string($value)) {
            return null;
        }

        $classes = collect(explode(',', $value))
            ->map(static fn (string $class): string => mb_trim($class))
            ->filter()
            ->values()
            ->all();

        return $classes === [] ? null : $classes;
    }
}
