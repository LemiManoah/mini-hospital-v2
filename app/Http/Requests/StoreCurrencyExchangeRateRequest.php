<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FacilityBranch;
use App\Support\BranchContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $branch = BranchContext::getActiveBranch();

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
                        ->where('facility_branch_id', $branch?->id)
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $branch = BranchContext::getActiveBranch();

            if (! $branch instanceof FacilityBranch) {
                $validator->errors()->add('facility_branch_id', 'Select an active branch before setting exchange rates.');

                return;
            }

            if (! $branch->multi_currency_enabled) {
                $validator->errors()->add('facility_branch_id', 'Enable multi-currency before setting exchange rates.');

                return;
            }

            $selectedCurrencyIds = $branch->supportedCurrencies()
                ->pluck('currencies.id')
                ->all();

            foreach (['from_currency_id', 'to_currency_id'] as $field) {
                $currencyId = $this->input($field);

                if (is_string($currencyId) && ! in_array($currencyId, $selectedCurrencyIds, true)) {
                    $validator->errors()->add($field, 'Only branch-selected currencies can be used for exchange rates.');
                }
            }
        });
    }
}
