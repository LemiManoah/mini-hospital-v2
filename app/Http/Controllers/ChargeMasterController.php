<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateChargeMasterPrice;
use App\Enums\BillableItemType;
use App\Http\Requests\UpdateChargeMasterPriceRequest;
use App\Models\ChargeMaster;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ChargeMasterController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:charge_masters.view', only: ['index']),
            new Middleware('permission:charge_masters.update', only: ['edit', 'update']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));

        $chargeMasters = $this->scopedChargeMasters()
            ->when($search !== '', static fn (Builder $query): Builder => $query
                ->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->whereLike('item_code', sprintf('%%%s%%', $search))
                        ->orWhereLike('description', sprintf('%%%s%%', $search));
                }))
            ->when($type !== '', static fn (Builder $query): Builder => $query->where('billable_type', $type))
            ->orderBy('billable_type')
            ->orderBy('description')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('charge-master/index', [
            'chargeMasters' => $chargeMasters,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
            'billableTypeOptions' => collect(BillableItemType::cases())
                ->map(static fn (BillableItemType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function edit(ChargeMaster $chargeMaster): Response
    {
        $this->authorizeChargeMaster($chargeMaster);

        return Inertia::render('charge-master/edit', [
            'chargeMaster' => $chargeMaster,
        ]);
    }

    public function update(
        UpdateChargeMasterPriceRequest $request,
        ChargeMaster $chargeMaster,
        UpdateChargeMasterPrice $action,
    ): RedirectResponse {
        $this->authorizeChargeMaster($chargeMaster);

        $action->handle($chargeMaster, $request->validated());

        return to_route('charge-masters.index')->with('success', 'Charge master price updated successfully.');
    }

    /**
     * @return Builder<ChargeMaster>
     */
    private function scopedChargeMasters(): Builder
    {
        $tenantId = auth()->user()?->tenant_id;
        $branchId = BranchContext::getActiveBranchId();

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);
        abort_unless(is_string($branchId) && $branchId !== '', 403);

        return ChargeMaster::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($branchId): void {
                $query->where('facility_branch_id', $branchId)
                    ->orWhereNull('facility_branch_id');
            });
    }

    private function authorizeChargeMaster(ChargeMaster $chargeMaster): void
    {
        $tenantId = auth()->user()?->tenant_id;
        $branchId = BranchContext::getActiveBranchId();

        abort_unless(
            is_string($tenantId)
                && $tenantId !== ''
                && $chargeMaster->tenant_id === $tenantId
                && is_string($branchId)
                && $branchId !== ''
                && ($chargeMaster->facility_branch_id === null || $chargeMaster->facility_branch_id === $branchId),
            403,
            'You do not have access to this charge master row in the active branch.',
        );
    }
}
