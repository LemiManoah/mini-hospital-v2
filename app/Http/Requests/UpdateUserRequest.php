<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\User\UpdateUserDTO;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        assert($user instanceof User);

        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            // allow roles to be synced when provided
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,id'],
        ];
    }

    public function updateDto(): UpdateUserDTO
    {
        return UpdateUserDTO::fromRequest($this);
    }
}
