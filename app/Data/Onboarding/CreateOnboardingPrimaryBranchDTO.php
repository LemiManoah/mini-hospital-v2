<?php

declare(strict_types=1);

namespace App\Data\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateOnboardingPrimaryBranchDTO
{
    public function __construct(
        public string $name,
        public string $branchCode,
        public ?string $email,
        public ?string $mainContact,
        public ?string $otherContact,
        public string $currencyId,
        public string $addressId,
        public ?string $countryId,
        public bool $hasStore,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   name: string,
         *   branch_code: string,
         *   email?: string|null,
         *   main_contact?: string|null,
         *   other_contact?: string|null,
         *   currency_id: string,
         *   address_id: string,
         *   country_id?: string|null,
         *   has_store?: bool
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            branchCode: $validated['branch_code'],
            email: self::nullableString($validated['email'] ?? null),
            mainContact: self::nullableString($validated['main_contact'] ?? null),
            otherContact: self::nullableString($validated['other_contact'] ?? null),
            currencyId: $validated['currency_id'],
            addressId: $validated['address_id'],
            countryId: self::nullableString($validated['country_id'] ?? null),
            hasStore: $validated['has_store'] ?? false,
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
