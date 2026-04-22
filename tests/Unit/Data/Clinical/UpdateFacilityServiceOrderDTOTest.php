<?php

declare(strict_types=1);

use App\Data\Clinical\UpdateFacilityServiceOrderDTO;
use Illuminate\Foundation\Http\FormRequest;

function updateFacilityServiceOrderRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
    {
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };
}

it('builds an update facility service order dto from validated input', function (): void {
    $dto = UpdateFacilityServiceOrderDTO::fromRequest(updateFacilityServiceOrderRequest([
        'facility_service_id' => 'service-2',
    ]));

    expect($dto->facilityServiceId)->toBe('service-2');
});
