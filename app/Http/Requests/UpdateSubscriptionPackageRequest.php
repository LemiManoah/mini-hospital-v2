<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GeneralStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSubscriptionPackageRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subscription_packages', 'name')->ignore($this->route('subscription_package')),
            ],
            'users' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('subscription_packages', 'users')->ignore($this->route('subscription_package')),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(GeneralStatus::class)],
        ];
    }
}
