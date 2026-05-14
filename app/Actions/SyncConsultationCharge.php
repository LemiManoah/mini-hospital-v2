<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use App\Models\Consultation;
use App\Models\FacilityService;
use App\Models\PatientVisit;
use App\Models\VisitCharge;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncConsultationCharge
{
    public function __construct(
        private ResolveConsultationFacilityService $resolveConsultationFacilityService,
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(Consultation $consultation): void
    {
        $consultation->loadMissing('visit.payer');

        $visit = $consultation->visit;

        if (! $visit instanceof PatientVisit) {
            return;
        }

        $service = $this->resolveConsultationFacilityService->handle($consultation);
        $chargeMaster = $service?->chargeMaster;

        if (! $service instanceof FacilityService || ! $chargeMaster instanceof ChargeMaster) {
            $this->removeExistingCharge($consultation, $visit);

            return;
        }

        $pricing = $this->resolveVisitChargeAmount->resolveChargeMaster($visit, $chargeMaster);

        if (! $pricing instanceof VisitChargePricing) {
            $this->removeExistingCharge($consultation, $visit);

            return;
        }

        $this->upsertVisitCharge->handle(
            $visit,
            $consultation,
            sprintf('Consultation: %s', $chargeMaster->description),
            $pricing->unitPrice,
            1,
            $chargeMaster->item_code,
            $chargeMaster->id,
            copayAmount: $pricing->copayAmount,
        );
    }

    private function removeExistingCharge(Consultation $consultation, PatientVisit $visit): void
    {
        VisitCharge::query()
            ->where('patient_visit_id', $visit->id)
            ->where('source_type', $consultation->getMorphClass())
            ->where('source_id', $consultation->id)
            ->delete();

        $visit->loadMissing('billing');

        if ($visit->billing !== null) {
            $this->recalculateVisitBilling->handle($visit->billing);
        }
    }
}
