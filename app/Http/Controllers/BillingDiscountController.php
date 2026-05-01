<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveBillingDiscount;
use App\Actions\RequestBillingDiscount;
use App\Actions\ReverseBillingDiscount;
use App\Http\Requests\ReverseBillingDiscountRequest;
use App\Http\Requests\StoreBillingDiscountRequest;
use App\Models\BillingDiscount;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class BillingDiscountController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:billing_discounts.create', only: ['store']),
            new Middleware('permission:billing_discounts.approve', only: ['approve']),
            new Middleware('permission:billing_discounts.reverse', only: ['reverse']),
        ];
    }

    public function store(
        StoreBillingDiscountRequest $request,
        PatientVisit $visit,
        RequestBillingDiscount $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);
        $visit->loadMissing('billing');

        abort_if($visit->billing === null, 422, 'This visit has no billing record for discounting.');

        $action->handle(
            $visit->billing,
            $request->amount(),
            $request->reason(),
            $request->notes(),
        );

        return to_route('finance.opd-payments.show', $visit)->with('success', 'Discount requested successfully.');
    }

    public function approve(
        PatientVisit $visit,
        BillingDiscount $discount,
        ApproveBillingDiscount $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);
        $this->authorizeDiscountForVisit($visit, $discount);

        $action->handle($discount);

        return to_route('finance.opd-payments.show', $visit)->with('success', 'Discount approved successfully.');
    }

    public function reverse(
        ReverseBillingDiscountRequest $request,
        PatientVisit $visit,
        BillingDiscount $discount,
        ReverseBillingDiscount $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($visit);
        $this->authorizeDiscountForVisit($visit, $discount);

        $action->handle($discount, $request->reversalReason());

        return to_route('finance.opd-payments.show', $visit)->with('success', 'Discount reversed successfully.');
    }

    private function authorizeDiscountForVisit(PatientVisit $visit, BillingDiscount $discount): void
    {
        abort_unless(
            $discount->patient_visit_id === $visit->id
                && $discount->tenant_id === $visit->tenant_id
                && $discount->facility_branch_id === $visit->facility_branch_id,
            404,
        );
    }
}
