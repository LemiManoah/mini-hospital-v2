<?php

declare(strict_types=1);

namespace App\Data\Patient;

use Illuminate\Foundation\Http\FormRequest;

final readonly class UpdateInsurancePackageDTO
{
    public function __construct(
        public string $insuranceCompanyId,
        public string $name,
        public string $status,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   insurance_company_id: string,
         *   name: string,
         *   status: string
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            insuranceCompanyId: $validated['insurance_company_id'],
            name: self::trimmed($validated['name']) ?? $validated['name'],
            status: $validated['status'],
        );
    }

    /**
     * @return array{
     *   insurance_company_id: string,
     *   name: string,
     *   status: string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'insurance_company_id' => $this->insuranceCompanyId,
            'name' => $this->name,
            'status' => $this->status,
        ];
    }

    private static function trimmed(string $value): ?string
    {
        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
