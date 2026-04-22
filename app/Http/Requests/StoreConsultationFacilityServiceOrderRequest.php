<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Clinical\CreateFacilityServiceOrderDTO;
use App\Data\Clinical\UpdateFacilityServiceOrderDTO;
use App\Models\FacilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreConsultationFacilityServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_service_id' => ['required', 'string', 'exists:facility_services,id'],
        ];
    }

    public function createDto(): CreateFacilityServiceOrderDTO
    {
        /** @var array{facility_service_id: string} $validated */
        $validated = $this->validated();

        return CreateFacilityServiceOrderDTO::fromArray($validated);
    }

    public function updateDto(): UpdateFacilityServiceOrderDTO
    {
        /** @var array{facility_service_id: string} $validated */
        $validated = $this->validated();

        return UpdateFacilityServiceOrderDTO::fromArray($validated);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $serviceId = $this->input('facility_service_id');
            if (! is_string($serviceId) || $serviceId === '') {
                return;
            }

            $service = FacilityService::query()->find($serviceId);
            if ($service === null) {
                return;
            }

            if (! $service->is_active) {
                $validator->errors()->add('facility_service_id', 'Only active facility services can be ordered.');
            }

        });
    }
}
