<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CreateUserRequest extends FormRequest
{
    /**

     * @return array<string, mixed>

     */

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'string', 'exists:staff,id'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'email',
                new ValidEmail,
                Rule::unique(User::class),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
            // roles are optional but must be an array of valid role ids when present
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,id'],
        ];
    }
}


