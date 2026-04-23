<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\User\UpdateUserDTO;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateManagedUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $targetUser = $this->route('user');
        assert($targetUser instanceof User);

        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($targetUser->id),
            ],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,id'],
        ];
    }

    public function updateDto(): UpdateUserDTO
    {
        return UpdateUserDTO::fromRequest($this);
    }
}
