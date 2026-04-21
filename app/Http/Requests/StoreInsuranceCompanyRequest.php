<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreInsuranceCompanyRequest extends FormRequest
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
        $tenantId = (string) $this->user()?->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('insurance_companies', 'name')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)
                ),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'main_contact' => ['nullable', 'string', 'max:20'],
            'other_contact' => ['nullable', 'string', 'max:20'],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'status' => ['required', new Enum(GeneralStatus::class)],
        ];
    }
}


