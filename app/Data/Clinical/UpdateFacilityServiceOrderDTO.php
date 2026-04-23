<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class UpdateFacilityServiceOrderDTO
{
    public function __construct(
        public string $facilityServiceId,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{facility_service_id: string} $validated */
        $validated = $request->validated();

        return new self(
            facilityServiceId: $validated['facility_service_id'],
        );
    }
}
