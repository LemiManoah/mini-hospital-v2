<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\Consultation;
use App\Models\ConsultationTariff;
use App\Models\PatientVisit;
use App\Models\VisitCharge;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncConsultationCharge
{
    public function __construct(
        private ResolveConsultationTariff $resolveConsultationTariff,
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

        $tariff = $this->resolveConsultationTariff->handle($consultation);
        $service = $tariff?->facilityService;

        if (! $tariff instanceof ConsultationTariff || $service === null || ! $service->is_billable || ! $tariff->is_active) {
            $this->removeExistingCharge($consultation, $visit);

            return;
        }

        $pricing = $this->resolveVisitChargeAmount->resolve(
            $visit,
            BillableItemType::SERVICE,
            $service->id,
            $service->selling_price === null ? null : (float) $service->selling_price,
        );

        if (! $pricing instanceof VisitChargePricing) {
            $this->removeExistingCharge($consultation, $visit);

            return;
        }

        $this->upsertVisitCharge->handle(
            $visit,
            $consultation,
            sprintf('Consultation fee: %s', $service->name),
            $pricing->unitPrice,
            1,
            $service->service_code,
            $service->charge_master_id,
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
