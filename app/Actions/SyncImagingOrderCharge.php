<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use App\Models\ImagingOrder;
use App\Models\ImagingStudyCatalog;
use App\Models\PatientVisit;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncImagingOrderCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private SyncImagingStudyCatalogChargeMaster $syncImagingStudyCatalogChargeMaster,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(ImagingOrder $order): void
    {
        $order->loadMissing(['visit.payer', 'studyCatalog.chargeMaster']);
        $visit = $order->visit;
        $studyCatalog = $order->studyCatalog;

        if (! $visit instanceof PatientVisit || ! $studyCatalog instanceof ImagingStudyCatalog) {
            return;
        }

        $chargeMaster = $this->chargeMasterFor($studyCatalog);

        if (! $chargeMaster instanceof ChargeMaster) {
            return;
        }

        $pricing = $this->resolveVisitChargeAmount->resolveChargeMaster($visit, $chargeMaster);

        if (! $pricing instanceof VisitChargePricing) {
            return;
        }

        $this->upsertVisitCharge->handle(
            $visit,
            $order,
            sprintf('Imaging order: %s', $studyCatalog->name),
            $pricing->unitPrice,
            1,
            $chargeMaster->item_code,
            $chargeMaster->id,
            copayAmount: $pricing->copayAmount,
        );
    }

    private function chargeMasterFor(ImagingStudyCatalog $studyCatalog): ?ChargeMaster
    {
        $studyCatalog->loadMissing('chargeMaster');

        if ($studyCatalog->chargeMaster instanceof ChargeMaster) {
            return $studyCatalog->chargeMaster;
        }

        return $this->syncImagingStudyCatalogChargeMaster->handle($studyCatalog);
    }
}
