<?php

declare(strict_types=1);

use App\Data\Clinical\UpdateFacilityServiceOrderDTO;

it('builds an update facility service order dto from validated input', function (): void {
    $dto = UpdateFacilityServiceOrderDTO::fromArray([
        'facility_service_id' => 'service-2',
    ]);

    expect($dto->facilityServiceId)->toBe('service-2');
});
