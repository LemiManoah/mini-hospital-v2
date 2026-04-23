<?php

declare(strict_types=1);

namespace App\Data\Patient;

use Illuminate\Foundation\Http\FormRequest;

final readonly class UpdatePatientDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $middleName,
        public string $ageInputMode,
        public ?string $dateOfBirth,
        public ?int $age,
        public ?string $ageUnits,
        public string $gender,
        public ?string $email,
        public string $phoneNumber,
        public ?string $alternativePhone,
        public ?string $nextOfKinName,
        public ?string $nextOfKinPhone,
        public ?string $nextOfKinRelationship,
        public ?string $addressId,
        public ?string $maritalStatus,
        public ?string $occupation,
        public ?string $religion,
        public ?string $countryId,
        public ?string $bloodGroup,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   first_name: string,
         *   last_name: string,
         *   middle_name?: string|null,
         *   age_input_mode: string,
         *   date_of_birth?: string|null,
         *   age?: int|numeric-string|null,
         *   age_units?: string|null,
         *   gender: string,
         *   email?: string|null,
         *   phone_number: string,
         *   alternative_phone?: string|null,
         *   next_of_kin_name?: string|null,
         *   next_of_kin_phone?: string|null,
         *   next_of_kin_relationship?: string|null,
         *   address_id?: string|null,
         *   marital_status?: string|null,
         *   occupation?: string|null,
         *   religion?: string|null,
         *   country_id?: string|null,
         *   blood_group?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            middleName: self::nullableString($validated['middle_name'] ?? null),
            ageInputMode: $validated['age_input_mode'],
            dateOfBirth: self::nullableString($validated['date_of_birth'] ?? null),
            age: self::nullableInt($validated['age'] ?? null),
            ageUnits: self::nullableString($validated['age_units'] ?? null),
            gender: $validated['gender'],
            email: self::nullableString($validated['email'] ?? null),
            phoneNumber: $validated['phone_number'],
            alternativePhone: self::nullableString($validated['alternative_phone'] ?? null),
            nextOfKinName: self::nullableString($validated['next_of_kin_name'] ?? null),
            nextOfKinPhone: self::nullableString($validated['next_of_kin_phone'] ?? null),
            nextOfKinRelationship: self::nullableString($validated['next_of_kin_relationship'] ?? null),
            addressId: self::nullableString($validated['address_id'] ?? null),
            maritalStatus: self::nullableString($validated['marital_status'] ?? null),
            occupation: self::nullableString($validated['occupation'] ?? null),
            religion: self::nullableString($validated['religion'] ?? null),
            countryId: self::nullableString($validated['country_id'] ?? null),
            bloodGroup: self::nullableString($validated['blood_group'] ?? null),
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
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
