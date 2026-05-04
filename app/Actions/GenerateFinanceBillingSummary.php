<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BillingDeposit;
use App\Models\BillingDiscount;
use App\Models\BillingWriteOff;
use App\Models\InsuranceCompanyInvoice;
use App\Models\Payment;
use App\Models\VisitBilling;
use App\Support\ActiveBranchWorkspace;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class GenerateFinanceBillingSummary
{
    public function __construct(private ActiveBranchWorkspace $activeBranchWorkspace) {}

    /**
     * @return array{
     *     summary: array<string, float>,
     *     paymentMethods: list<array{payment_method: string, amount: float, count: int}>,
     *     depositStatuses: list<array{status: string, amount: float, applied_amount: float, held_amount: float, count: int}>,
     *     insurerInvoices: array{count: int, balance: float}
     * }
     */
    public function handle(CarbonInterface $start, CarbonInterface $end): array
    {
        $billings = $this->activeBranchWorkspace
            ->apply(VisitBilling::query())
            ->whereBetween('created_at', [$start, $end]);

        $payments = $this->activeBranchWorkspace
            ->apply(Payment::query())
            ->whereBetween('payment_date', [$start, $end]);

        $deposits = $this->activeBranchWorkspace
            ->apply(BillingDeposit::query())
            ->whereBetween('received_at', [$start, $end]);

        $insuranceInvoices = $this->activeBranchWorkspace
            ->apply(InsuranceCompanyInvoice::query())
            ->whereBetween('created_at', [$start, $end]);

        $discounts = $this->activeBranchWorkspace
            ->apply(BillingDiscount::query())
            ->whereBetween('approved_at', [$start, $end])
            ->approved();

        $writeOffs = $this->activeBranchWorkspace
            ->apply(BillingWriteOff::query())
            ->whereBetween('approved_at', [$start, $end])
            ->approved();

        $currentDebtors = $this->activeBranchWorkspace
            ->apply(VisitBilling::query())
            ->where('balance_amount', '>', 0);

        $insurerInvoicesBilled = $this->sumClone($insuranceInvoices, 'bill_amount');
        $insurerInvoicesPaid = $this->sumClone($insuranceInvoices, 'paid_amount');

        return [
            'summary' => [
                'gross_billings' => $this->sumClone($billings, 'gross_amount'),
                'patient_collections' => $this->sumClone((clone $payments)->where('is_refund', false), 'amount'),
                'patient_refunds' => $this->sumClone((clone $payments)->where('is_refund', true), 'amount'),
                'approved_discounts' => $this->sumClone($discounts, 'amount'),
                'approved_write_offs' => $this->sumClone($writeOffs, 'amount'),
                'deposits_received' => $this->sumClone($deposits, 'amount'),
                'deposits_applied' => $this->sumClone($deposits, 'applied_amount'),
                'deposits_held' => $this->sumDepositHeld($deposits),
                'insurer_invoices_billed' => $insurerInvoicesBilled,
                'insurer_invoices_paid' => $insurerInvoicesPaid,
                'current_debtor_balance' => $this->sumClone($currentDebtors, 'balance_amount'),
            ],
            'paymentMethods' => $this->paymentMethodBreakdown($payments),
            'depositStatuses' => $this->depositStatusBreakdown($deposits),
            'insurerInvoices' => [
                'count' => (clone $insuranceInvoices)->count(),
                'balance' => round($insurerInvoicesBilled - $insurerInvoicesPaid, 2),
            ],
        ];
    }

    private static function floatAttribute(Model $model, string $attribute): float
    {
        $value = $model->getAttribute($attribute);

        return round(is_numeric($value) ? (float) $value : 0.0, 2);
    }

    private static function intAttribute(Model $model, string $attribute): int
    {
        $value = $model->getAttribute($attribute);

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function sumClone(Builder $query, string $column): float
    {
        $sum = (clone $query)->sum($column);

        return round((float) $sum, 2);
    }

    /**
     * @param  Builder<BillingDeposit>  $query
     */
    private function sumDepositHeld(Builder $query): float
    {
        $heldTotal = (clone $query)
            ->selectRaw('COALESCE(SUM(amount - applied_amount - refunded_amount), 0) as held_total')
            ->value('held_total');

        return round(is_numeric($heldTotal) ? (float) $heldTotal : 0.0, 2);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return list<array{payment_method: string, amount: float, count: int}>
     */
    private function paymentMethodBreakdown(Builder $query): array
    {
        $breakdown = (clone $query)
            ->where('is_refund', false)
            ->select('payment_method', DB::raw('SUM(amount) as amount'), DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('payment_method')
            ->orderByDesc('amount')
            ->get()
            ->map(static fn (Payment $payment): array => [
                'payment_method' => (string) ($payment->payment_method ?? 'unknown'),
                'amount' => self::floatAttribute($payment, 'amount'),
                'count' => self::intAttribute($payment, 'aggregate_count'),
            ])
            ->values()
            ->all();

        return array_values($breakdown);
    }

    /**
     * @param  Builder<BillingDeposit>  $query
     * @return list<array{status: string, amount: float, applied_amount: float, held_amount: float, count: int}>
     */
    private function depositStatusBreakdown(Builder $query): array
    {
        $breakdown = (clone $query)
            ->select(
                'status',
                DB::raw('SUM(amount) as amount'),
                DB::raw('SUM(applied_amount) as applied_amount'),
                DB::raw('SUM(amount - applied_amount - refunded_amount) as held_amount'),
                DB::raw('COUNT(*) as aggregate_count'),
            )
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(static fn (BillingDeposit $deposit): array => [
                'status' => is_string($deposit->getRawOriginal('status')) ? $deposit->getRawOriginal('status') : 'unknown',
                'amount' => self::floatAttribute($deposit, 'amount'),
                'applied_amount' => self::floatAttribute($deposit, 'applied_amount'),
                'held_amount' => self::floatAttribute($deposit, 'held_amount'),
                'count' => self::intAttribute($deposit, 'aggregate_count'),
            ])
            ->values()
            ->all();

        return array_values($breakdown);
    }
}
