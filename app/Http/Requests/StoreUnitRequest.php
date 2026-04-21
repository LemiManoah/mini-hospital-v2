<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUnitRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('units', 'name')],
            'symbol' => ['required', 'string', 'max:20', Rule::unique('units', 'symbol')],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(UnitType::class)],
        ];
    }
}


