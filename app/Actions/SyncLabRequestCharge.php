<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\LabRequest;

final readonly class SyncLabRequestCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(LabRequest $request): void
    {
        $request->loadMissing(['visit.payer', 'items.test']);

        $total = $request->items->sum(function ($item) use ($request): float {
            $resolved = $this->resolveVisitChargeAmount->handle(
                $request->visit,
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
            $request->visit,
            $request,
            $description,
            $total,
            1,
            'LAB-REQUEST',
        );
    }
}
