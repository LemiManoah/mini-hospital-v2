<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsultationType;
use App\Models\Consultation;
use App\Models\FacilityService;
use Illuminate\Database\Eloquent\Builder;

final class ResolveConsultationFacilityService
{
    public function handle(Consultation $consultation): ?FacilityService
    {
        $consultation->loadMissing('visit');

        $visit = $consultation->visit;

        if ($visit === null) {
            return null;
        }

        $consultationType = $consultation->consultation_type instanceof ConsultationType
            ? $consultation->consultation_type
            : ConsultationType::defaultForVisit($visit);

        return FacilityService::query()
            ->with('chargeMaster')
            ->where('tenant_id', $visit->tenant_id)
            ->where('is_consultation', true)
            ->where('is_billable', true)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($consultationType): void {
                $query->where('consultation_type', $consultationType->value)
                    ->orWhereNull('consultation_type');
            })
            ->orderByRaw('CASE WHEN consultation_type IS NULL THEN 1 ELSE 0 END')
            ->latest()
            ->first();
    }
}
