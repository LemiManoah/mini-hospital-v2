<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCountryRequest extends FormRequest
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
            'country_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('countries', 'country_name')->ignore($this->route('country')),
            ],
            'country_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('countries', 'country_code')->ignore($this->route('country')),
            ],
            'dial_code' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
        ];
    }
}


