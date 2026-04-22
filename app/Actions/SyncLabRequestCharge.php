<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\LabRequest;
use App\Models\LabRequestItem;

final readonly class SyncLabRequestCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(LabRequest $request): void
    {
        $request->loadMissing(['visit.payer', 'items.test']);
        $visit = $request->visit;
        if (! $visit instanceof \App\Models\PatientVisit) {
            return;
        }

        $total = $request->items->sum(function (LabRequestItem $item) use ($visit): float {
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
            ? 'Lab request: 1 test'
            : sprintf('Lab request: %d tests', $testCount);

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
