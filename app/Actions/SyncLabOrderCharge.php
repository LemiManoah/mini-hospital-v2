<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\PatientVisit;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncLabOrderCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(LabOrder $request): void
    {
        $request->loadMissing(['visit.payer', 'items.test']);
        $visit = $request->visit;
        if (! $visit instanceof PatientVisit) {
            return;
        }

        $copayAmount = 0.0;
        $total = $request->items->sum(function (LabOrderItem $item) use ($visit, &$copayAmount): float {
            $pricing = $this->resolveVisitChargeAmount->resolve(
                $visit,
                BillableItemType::TEST,
                $item->test_id,
                (float) $item->price,
            );

            if (! $pricing instanceof VisitChargePricing) {
                return 0.0;
            }

            $copayAmount += $pricing->copayAmount;

            return $pricing->unitPrice;
        });

        $testCount = $request->items->count();
        $description = $testCount === 1
            ? 'Lab order: 1 test'
            : sprintf('Lab order: %d tests', $testCount);

        $this->upsertVisitCharge->handle(
            $visit,
            $request,
            $description,
            $total,
            1,
            'LAB-REQUEST',
            copayAmount: $copayAmount,
        );
    }
}
