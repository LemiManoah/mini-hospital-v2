<?php

declare(strict_types=1);

namespace App\Data\Clinical;

final readonly class UpdateFacilityServiceOrderDTO
{
    public function __construct(
        public string $facilityServiceId,
    ) {}

    /**
     * @param  array{facility_service_id: string}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            facilityServiceId: $attributes['facility_service_id'],
        );
    }
}
