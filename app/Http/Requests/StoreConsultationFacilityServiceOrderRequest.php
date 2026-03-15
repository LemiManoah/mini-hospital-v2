<?php

declare(strict_types=1);

namespace App\Http\Requests;

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
            'clinical_notes' => ['nullable', 'string'],
            'service_instructions' => ['nullable', 'string'],
        ];
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

            if ($service->is_billable && ($service->charge_master_id === null || $service->charge_master_id === '')) {
                $validator->errors()->add('facility_service_id', 'This billable facility service is missing a charge master mapping.');
            }
        });
    }
}
