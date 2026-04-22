<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StoreCurrencyExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('currency_exchange_rates.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()?->tenant_id;

        return [
            'from_currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'to_currency_id' => [
                'required',
                'uuid',
                'exists:currencies,id',
                'different:from_currency_id',
                Rule::unique('currency_exchange_rates')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('tenant_id', $tenantId)
                        ->where('from_currency_id', $this->input('from_currency_id'))
                        ->where('effective_date', $this->input('effective_date'))
                    ),
            ],
            'rate' => ['required', 'numeric', 'min:0.000001'],
            'effective_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_currency_id.unique' => 'An exchange rate for this currency pair already exists on the selected date.',
            'to_currency_id.different' => 'The source and target currencies must be different.',
        ];
    }
}
