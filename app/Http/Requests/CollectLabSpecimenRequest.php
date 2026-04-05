<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LabRequestItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CollectLabSpecimenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specimen_type_id' => ['required', 'uuid'],
            'outside_sample' => ['nullable', 'boolean'],
            'outside_sample_origin' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'redirect_to' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $labRequestItem = $this->labRequestItem();

            if (! $labRequestItem instanceof LabRequestItem) {
                return;
            }

            $specimenTypeId = (string) $this->input('specimen_type_id', '');
            $labTest = $labRequestItem->test()->first();
            $isAllowedSpecimenType = $specimenTypeId !== ''
                && $labTest?->specimenTypes()->whereKey($specimenTypeId)->exists();

            if (! $isAllowedSpecimenType) {
                $validator->errors()->add('specimen_type_id', 'Choose a specimen type configured for this test.');
            }

            if ($this->boolean('outside_sample') && mb_trim((string) $this->input('outside_sample_origin', '')) === '') {
                $validator->errors()->add('outside_sample_origin', 'Describe where the outside sample came from.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'outside_sample' => $this->boolean('outside_sample') || $this->filled('outside_sample_origin'),
            'outside_sample_origin' => $this->filled('outside_sample_origin') ? $this->input('outside_sample_origin') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
            'redirect_to' => $this->filled('redirect_to') ? $this->input('redirect_to') : null,
        ]);
    }

    private function labRequestItem(): mixed
    {
        return $this->route('labRequestItem') ?? $this->route('lab_request_item');
    }
}
