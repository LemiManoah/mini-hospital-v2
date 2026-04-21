<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FacilityLevel;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreWorkspaceRegistrationRequest extends FormRequest
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
            'owner_name' => ['required', 'string', 'max:120'],
            'workspace_name' => ['required', 'string', 'max:100', Rule::unique(Tenant::class, 'name')],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class, 'email'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'subscription_package_id' => ['required', 'string', Rule::exists(SubscriptionPackage::class, 'id')],
            'facility_level' => ['required', Rule::enum(FacilityLevel::class)],
            'country_id' => ['nullable', 'string', 'exists:countries,id'],
            'domain' => ['nullable', 'string', 'max:100', Rule::unique(Tenant::class, 'domain')],
        ];
    }
}


