<?php

declare(strict_types=1);

namespace App\Data\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateOnboardingDepartmentsDTO
{
    /**
     * @param  list<CreateOnboardingDepartmentDTO>  $departments
     */
    public function __construct(
        public array $departments,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   departments: list<array{
         *     name: string,
         *     location?: string|null,
         *     is_clinical?: bool
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            departments: array_map(
                CreateOnboardingDepartmentDTO::fromPayload(...),
                $validated['departments'],
            ),
        );
    }
}
