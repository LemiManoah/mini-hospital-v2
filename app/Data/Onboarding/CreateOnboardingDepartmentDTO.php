<?php

declare(strict_types=1);

namespace App\Data\Onboarding;

final readonly class CreateOnboardingDepartmentDTO
{
    public function __construct(
        public string $name,
        public ?string $location,
        public bool $isClinical,
    ) {}

    /**
     * @param  array{
     *   name: string,
     *   location?: string|null,
     *   is_clinical?: bool
     * } $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            name: mb_trim($payload['name']),
            location: self::nullableString($payload['location'] ?? null),
            isClinical: $payload['is_clinical'] ?? true,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
