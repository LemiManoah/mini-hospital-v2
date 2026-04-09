<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabRequest;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingLabRequest
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
    ) {}

    public function handle(LabRequest $labRequest): void
    {
        $visit = $labRequest->visit()->with(['payer', 'billing'])->firstOrFail();

        DB::transaction(function () use ($labRequest, $visit): void {
            VisitCharge::query()
                ->where('patient_visit_id', $labRequest->visit_id)
                ->where('source_type', $labRequest->getMorphClass())
                ->where('source_id', $labRequest->id)
                ->delete();

            $labRequest->items()->delete();
            $labRequest->delete();

            $billing = $this->ensureVisitBilling->handle($visit);
            $this->recalculateVisitBilling->handle($billing);
        });
    }
}
