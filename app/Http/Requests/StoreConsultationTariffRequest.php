<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ConsultationType;
use App\Enums\VisitType;
use App\Models\ConsultationTariff;
use App\Models\FacilityService;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreConsultationTariffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'visit_type' => ['nullable', Rule::enum(VisitType::class)],
            'consultation_type' => ['required', Rule::enum(ConsultationType::class)],
            'facility_service_id' => ['required', 'uuid', Rule::exists('facility_services', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantId = $this->user()?->tenant_id;
            $branchId = BranchContext::getActiveBranchId();

            if (! is_string($tenantId) || $tenantId === '' || ! is_string($branchId) || $branchId === '') {
                $validator->errors()->add('facility_service_id', 'An active branch is required before managing consultation tariffs.');

                return;
            }

            $serviceId = $this->input('facility_service_id');

            if (
                ! is_string($serviceId)
                || ! FacilityService::query()
                    ->whereKey($serviceId)
                    ->where('tenant_id', $tenantId)
                    ->where('is_billable', true)
                    ->where('is_active', true)
                    ->exists()
            ) {
                $validator->errors()->add('facility_service_id', 'Choose an active billable facility service for consultation billing.');
            }

            $consultationType = $this->input('consultation_type');
            $visitType = $this->input('visit_type');

            if (! is_string($consultationType)) {
                return;
            }

            $query = ConsultationTariff::query()
                ->where('tenant_id', $tenantId)
                ->where('facility_branch_id', $branchId)
                ->where('consultation_type', $consultationType);

            if (is_string($visitType) && $visitType !== '') {
                $query->where('visit_type', $visitType);
            } else {
                $query->whereNull('visit_type');
            }

            $consultationTariff = $this->route('consultation_tariff');
            $existingId = $consultationTariff instanceof ConsultationTariff ? $consultationTariff->id : null;

            if (is_string($existingId) && $existingId !== '') {
                $query->whereKeyNot($existingId);
            }

            if ($query->exists()) {
                $validator->errors()->add('consultation_type', 'A tariff already exists for that visit type and consultation type in the active branch.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $visitType = $this->input('visit_type');

        $this->merge([
            'visit_type' => $visitType === 'all' ? null : $visitType,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
