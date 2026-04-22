<?php

declare(strict_types=1);

use App\Data\Clinical\CreateFacilityServiceOrderDTO;
use Illuminate\Foundation\Http\FormRequest;

if (! function_exists('createFacilityServiceOrderRequest')) {
    function createFacilityServiceOrderRequest(array $validated): FormRequest
    {
        return new class($validated) extends FormRequest
        {
            public function __construct(private array $validatedInput)
            {
                parent::__construct();
            }

            public function validated($key = null, $default = null): array
            {
                return $this->validatedInput;
            }
        };
    }
}

it('builds a create facility service order dto from validated input', function (): void {
    $dto = CreateFacilityServiceOrderDTO::fromRequest(createFacilityServiceOrderRequest([
        'facility_service_id' => 'service-1',
    ]));

    expect($dto->facilityServiceId)->toBe('service-1');
});
