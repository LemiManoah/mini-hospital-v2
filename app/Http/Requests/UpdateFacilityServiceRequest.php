<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdateFacilityServiceRequest extends StoreFacilityServiceRequest
{
    public function rules(): array
    {
        /** @var \App\Models\FacilityService $facilityService */
        $facilityService = $this->route('facility_service');

        return [
            ...parent::rules(),
            'service_code' => ['required', 'string', 'max:50', Rule::unique('facility_services', 'service_code')->ignore($facilityService->id)],
        ];
    }
}
