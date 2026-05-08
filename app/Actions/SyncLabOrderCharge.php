<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\PatientVisit;

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

        $total = $request->items->sum(function (LabOrderItem $item) use ($visit): float {
            $resolved = $this->resolveVisitChargeAmount->handle(
                $visit,
                BillableItemType::TEST,
                $item->test_id,
                (float) $item->price,
            );

            return $resolved ?? 0.0;
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
        );
    }
}
