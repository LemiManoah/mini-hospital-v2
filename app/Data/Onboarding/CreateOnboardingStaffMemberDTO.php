<?php

declare(strict_types=1);

namespace App\Data\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateOnboardingStaffMemberDTO
{
    /**
     * @param  list<string>  $departmentIds
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $middleName,
        public string $email,
        public ?string $phone,
        public array $departmentIds,
        public string $staffPositionId,
        public string $type,
        public ?string $licenseNumber,
        public ?string $specialty,
        public string $hireDate,
        public bool $isActive,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   first_name: string,
         *   last_name: string,
         *   middle_name?: string|null,
         *   email: string,
         *   phone?: string|null,
         *   department_ids: list<string>,
         *   staff_position_id: string,
         *   type: string,
         *   license_number?: string|null,
         *   specialty?: string|null,
         *   hire_date: string,
         *   is_active?: bool
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            middleName: self::nullableString($validated['middle_name'] ?? null),
            email: $validated['email'],
            phone: self::nullableString($validated['phone'] ?? null),
            departmentIds: $validated['department_ids'],
            staffPositionId: $validated['staff_position_id'],
            type: $validated['type'],
            licenseNumber: self::nullableString($validated['license_number'] ?? null),
            specialty: self::nullableString($validated['specialty'] ?? null),
            hireDate: $validated['hire_date'],
            isActive: $validated['is_active'] ?? true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toCreateStaffPayload(string $tenantId, string $branchId, string $userId): array
    {
        return [
            'tenant_id' => $tenantId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'email' => $this->email,
            'phone' => $this->phone,
            'department_ids' => $this->departmentIds,
            'staff_position_id' => $this->staffPositionId,
            'type' => $this->type,
            'license_number' => $this->licenseNumber,
            'specialty' => $this->specialty,
            'hire_date' => $this->hireDate,
            'branch_ids' => [$branchId],
            'primary_branch_id' => $branchId,
            'is_active' => $this->isActive,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
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
