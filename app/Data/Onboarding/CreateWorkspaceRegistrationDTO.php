<?php

declare(strict_types=1);

namespace App\Data\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateWorkspaceRegistrationDTO
{
    public function __construct(
        public string $ownerName,
        public string $workspaceName,
        public string $email,
        public string $subscriptionPackageId,
        public string $facilityLevel,
        public ?string $countryId,
        public ?string $domain,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     owner_name: string,
         *     workspace_name: string,
         *     email: string,
         *     subscription_package_id: string,
         *     facility_level: string,
         *     country_id?: string|null,
         *     domain?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            ownerName: $validated['owner_name'],
            workspaceName: $validated['workspace_name'],
            email: $validated['email'],
            subscriptionPackageId: $validated['subscription_package_id'],
            facilityLevel: $validated['facility_level'],
            countryId: self::nullableString($validated['country_id'] ?? null),
            domain: self::nullableString($validated['domain'] ?? null),
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
