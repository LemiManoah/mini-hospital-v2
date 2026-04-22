<?php

declare(strict_types=1);

use App\Data\Clinical\CreateFacilityServiceOrderDTO;

it('builds a create facility service order dto from validated input', function (): void {
    $dto = CreateFacilityServiceOrderDTO::fromArray([
        'facility_service_id' => 'service-1',
    ]);

    expect($dto->facilityServiceId)->toBe('service-1');
});
