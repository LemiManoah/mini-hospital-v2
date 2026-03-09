<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCurrencyRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies', 'code')->ignore($this->route('currency')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'modifiable' => ['required', 'boolean'],
        ];
    }
}
