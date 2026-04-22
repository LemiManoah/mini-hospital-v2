<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FacilityLevel;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOnboardingProfileRequest extends FormRequest
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
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Tenant::class, 'name')->ignore($tenantId)],
            'domain' => ['nullable', 'string', 'max:100', Rule::unique(Tenant::class, 'domain')->ignore($tenantId)],
            'facility_level' => ['required', Rule::enum(FacilityLevel::class)],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'country_id' => ['nullable', 'string', 'exists:countries,id'],
        ];
    }
}
