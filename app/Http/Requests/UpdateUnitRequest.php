<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');
        assert($unit instanceof Unit);

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'name')->ignore($unit->id),
            ],
            'symbol' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'symbol')->ignore($unit->id),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(UnitType::class)],
        ];
    }
}
