<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsultationType;
use App\Models\Consultation;
use App\Models\ConsultationTariff;
use Illuminate\Database\Eloquent\Builder;

final class ResolveConsultationTariff
{
    public function handle(Consultation $consultation): ?ConsultationTariff
    {
        $consultation->loadMissing('visit');

        $visit = $consultation->visit;

        if ($visit === null) {
            return null;
        }

        $consultationType = $consultation->consultation_type instanceof ConsultationType
            ? $consultation->consultation_type
            : ConsultationType::defaultForVisit($visit);

        return ConsultationTariff::query()
            ->with('facilityService')
            ->where('tenant_id', $visit->tenant_id)
            ->where('facility_branch_id', $visit->facility_branch_id)
            ->where('consultation_type', $consultationType->value)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($visit): void {
                $query->where('visit_type', $visit->visit_type?->value)
                    ->orWhereNull('visit_type');
            })
            ->orderByRaw('CASE WHEN visit_type IS NULL THEN 1 ELSE 0 END')
            ->latest()
            ->first();
    }
}
