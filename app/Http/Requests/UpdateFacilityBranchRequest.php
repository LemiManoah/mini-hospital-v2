<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use App\Models\FacilityBranch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateFacilityBranchRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var FacilityBranch $branch */
        $branch = $this->route('facility_branch');
        $tenantId = Auth::user()?->tenant_id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'branch_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('facility_branches', 'branch_code')
                    ->ignore($branch->id)
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->where('tenant_id', $tenantId)),
            ],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'status' => ['required', new Enum(GeneralStatus::class)],
            'main_contact' => ['nullable', 'string', 'max:255'],
            'other_contact' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'has_store' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_store' => $this->boolean('has_store'),
        ]);
    }
}
