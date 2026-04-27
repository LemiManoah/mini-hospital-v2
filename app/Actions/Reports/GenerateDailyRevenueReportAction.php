<?php

declare(strict_types=1);

namespace App\Actions\Reports;

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
    public function handle(CarbonInterface $date, string $branchId): array
    {
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

        $firstPayment = $payments->first();
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
            'branch_name' => $firstPayment?->branch?->name,
            'currency' => $firstPayment?->branch?->currency !== null
                ? $firstPayment->branch->currency->symbol
                : 'UGX',
            'total_amount' => $totalAmount,
            'total_count' => $income->count(),
            'refund_amount' => $refundAmount,
            'net_amount' => $totalAmount - $refundAmount,
            'by_method' => $byMethod,
            'rows' => $payments,
        ];
    }
}
