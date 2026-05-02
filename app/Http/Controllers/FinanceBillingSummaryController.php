<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateFinanceBillingSummary;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FinanceBillingSummaryController implements HasMiddleware
{
    public function __construct(private GenerateFinanceBillingSummary $summary) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.view', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $startDate = $request->date('start_date') ?? now()->startOfMonth();
        $endDate = $request->date('end_date') ?? now();
        $branchId = BranchContext::getActiveBranchId();

        abort_if(! is_string($branchId) || $branchId === '', 403, 'Select an active branch before viewing billing reports.');

        return Inertia::render('finance/billing-summary', [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            ...$this->summary->handle(
                start: $startDate->copy()->startOfDay(),
                end: $endDate->copy()->endOfDay(),
            ),
        ]);
    }
}
