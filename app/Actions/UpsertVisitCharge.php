<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VisitChargeStatus;
use App\Models\PatientVisit;
use App\Models\VisitCharge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final readonly class UpsertVisitCharge
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
    ) {}

    public function handle(
        PatientVisit $visit,
        Model $source,
        string $description,
        float $unitPrice,
        float $quantity = 1,
        ?string $chargeCode = null,
        ?string $notes = null,
    ): VisitCharge {
        $billing = $this->ensureVisitBilling->handle($visit);
        $userId = Auth::id();
        $lineTotal = round($unitPrice * $quantity, 2);

        $charge = VisitCharge::query()->updateOrCreate(
            [
                'patient_visit_id' => $visit->id,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
            ],
            [
                'tenant_id' => $visit->tenant_id,
                'facility_branch_id' => $visit->facility_branch_id,
                'visit_billing_id' => $billing->id,
                'charge_code' => $chargeCode,
                'description' => $description,
                'quantity' => round($quantity, 2),
                'unit_price' => round($unitPrice, 2),
                'line_total' => $lineTotal,
                'status' => VisitChargeStatus::ACTIVE,
                'charged_at' => now(),
                'notes' => $notes,
                'updated_by' => $userId,
                'created_by' => $userId,
            ],
        );

        $this->recalculateVisitBilling->handle($billing);

        return $charge->refresh();
    }
}
