<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InventoryLocationType;
use App\Support\BranchContext;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInventoryLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = BranchContext::getActiveBranchId();

        return [
            'name' => ['required', 'string', 'max:150'],
            'location_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('inventory_locations', 'location_code')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query
                        ->where('branch_id', $branchId)
                        ->whereNull('deleted_at')),
            ],
            'type' => ['required', Rule::enum(InventoryLocationType::class)],
            'description' => ['nullable', 'string'],
            'is_dispensing_point' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizedText('name'),
            'description' => $this->normalizedText('description'),
            'is_dispensing_point' => $this->boolean('is_dispensing_point'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    private function normalizedText(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
