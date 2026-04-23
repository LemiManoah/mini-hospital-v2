<?php

declare(strict_types=1);

namespace App\Data\User;

use Illuminate\Foundation\Http\FormRequest;

final readonly class UpdateUserDTO
{
    /**
     * @param  list<string>|null  $roles
     */
    public function __construct(
        public string $email,
        public ?array $roles,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     email: string,
         *     roles?: list<string>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            email: $validated['email'],
            roles: $validated['roles'] ?? null,
        );
    }
}
