<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use App\Models\FacilityBranch;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class GenerateDailyRevenueReportAction
{
    /**
     * @return array{
     *     date: string,
     *     branch_name: string|null,
     *     currency: string,
     *     total_amount: float,
     *     total_count: int,
     *     refund_amount: float,
     *     net_amount: float,
     *     by_method: array<string, float>,
     *     rows: Collection<int, Payment>
     * }
     */
    public function handle(CarbonInterface $date, string $branchId, ?string $displayCurrencyId = null): array
    {
        $branch = FacilityBranch::query()
            ->with('currency:id,code,symbol')
            ->find($branchId);

        /** @var Collection<int, Payment> $payments */
        $payments = Payment::query()
            ->with([
                'visit:id,patient_id,visit_number,facility_branch_id',
                'visit.patient:id,first_name,middle_name,last_name,patient_number',
                'branch:id,name,currency_id',
                'branch.currency:id,code,symbol',
            ])
            ->where('facility_branch_id', $branchId)
            ->whereDate('payment_date', $date)
            ->whereNull('deleted_at')
            ->oldest('payment_date')
            ->get();

        $firstPayment = $payments->first();
        $branchCurrencyId = $branch instanceof FacilityBranch ? $branch->currency_id : null;
        $paymentBranchCurrencyId = $firstPayment?->branch instanceof FacilityBranch ? $firstPayment->branch->currency_id : null;
        $baseCurrencyId = $branchCurrencyId ?? $paymentBranchCurrencyId;
        $displayRate = 1.0;
        $branchCurrency = $branch instanceof FacilityBranch ? $branch->currency : null;
        $paymentBranchCurrency = $firstPayment?->branch instanceof FacilityBranch ? $firstPayment->branch->currency : null;
        $displayCurrency = $branchCurrency ?? $paymentBranchCurrency;

        if (
            is_string($displayCurrencyId)
            && $displayCurrencyId !== ''
            && is_string($baseCurrencyId)
            && $displayCurrencyId !== $baseCurrencyId
        ) {
            $rate = $this->displayRate($branchId, $date, $baseCurrencyId, $displayCurrencyId);

            if (is_float($rate)) {
                $displayRate = $rate;
                $displayCurrency = Currency::query()->find($displayCurrencyId);
            }
        }

        $payments->each(static function (Payment $payment) use ($displayRate): void {
            $payment->setAttribute('amount', round((float) $payment->amount * $displayRate, 2));
        });

        /** @var Collection<int, Payment> $income */
        $income = $payments->where('is_refund', false);
        /** @var Collection<int, Payment> $refunds */
        $refunds = $payments->where('is_refund', true);

        /** @var array<string, float> $byMethod */
        $byMethod = $income
            ->groupBy('payment_method')
            ->map(
                static fn (Collection $group): float => $group->reduce(
                    static fn (float $carry, Payment $payment): float => $carry + (float) $payment->amount,
                    0.0,
                )
            )
            ->all();

        $totalAmount = $income->reduce(
            static fn (float $carry, Payment $payment): float => $carry + (float) $payment->amount,
            0.0,
        );
        $refundAmount = $refunds->reduce(
            static fn (float $carry, Payment $payment): float => $carry + (float) $payment->amount,
            0.0,
        );

        return [
            'date' => $date->format('d M Y'),
            'branch_name' => $this->branchName($branch, $firstPayment),
            'currency' => $displayCurrency !== null
                ? $displayCurrency->symbol
                : 'UGX',
            'total_amount' => $totalAmount,
            'total_count' => $income->count(),
            'refund_amount' => $refundAmount,
            'net_amount' => $totalAmount - $refundAmount,
            'by_method' => $byMethod,
            'rows' => $payments,
        ];
    }

    private function displayRate(string $branchId, CarbonInterface $date, string $baseCurrencyId, string $displayCurrencyId): ?float
    {
        $directRate = CurrencyExchangeRate::query()
            ->where('facility_branch_id', $branchId)
            ->where('from_currency_id', $baseCurrencyId)
            ->where('to_currency_id', $displayCurrencyId)
            ->where('effective_date', '<=', $date->toDateString())
            ->latest('effective_date')
            ->value('rate');

        if (is_numeric($directRate)) {
            return (float) $directRate;
        }

        $inverseRate = CurrencyExchangeRate::query()
            ->where('facility_branch_id', $branchId)
            ->where('from_currency_id', $displayCurrencyId)
            ->where('to_currency_id', $baseCurrencyId)
            ->where('effective_date', '<=', $date->toDateString())
            ->latest('effective_date')
            ->value('rate');

        if (! is_numeric($inverseRate) || (float) $inverseRate <= 0.0) {
            return null;
        }

        return round(1 / (float) $inverseRate, 10);
    }

    private function branchName(?FacilityBranch $branch, ?Payment $payment): ?string
    {
        if ($branch instanceof FacilityBranch) {
            return $branch->name;
        }

        if ($payment?->branch instanceof FacilityBranch) {
            return $payment->branch->name;
        }

        return null;
    }
}
