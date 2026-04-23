<?php

declare(strict_types=1);

namespace App\Data\User;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateUserDTO
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $staffId,
        public string $email,
        public array $roles,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     staff_id: string,
         *     email: string,
         *     roles?: list<string>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            staffId: $validated['staff_id'],
            email: $validated['email'],
            roles: $validated['roles'] ?? [],
        );
    }
}
