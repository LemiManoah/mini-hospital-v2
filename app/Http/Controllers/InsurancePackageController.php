<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsurancePackage;
use App\Actions\DeleteInsurancePackage;
use App\Actions\UpdateInsurancePackage;
use App\Enums\BillableItemType;
use App\Enums\InventoryItemType;
use App\Http\Requests\DeleteInsurancePackageRequest;
use App\Http\Requests\StoreInsurancePackageRequest;
use App\Http\Requests\UpdateInsurancePackageRequest;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\InsurancePackagePrice;
use App\Models\InventoryItem;
use App\Models\LabTestCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InsurancePackageController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.view', only: ['index', 'show']),
            new Middleware('permission:insurance_packages.create', only: ['create', 'store']),
            new Middleware('permission:insurance_packages.update', only: ['edit', 'update']),
            new Middleware('permission:insurance_packages.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $insurancePackages = InsurancePackage::query()
            ->with(['insuranceCompany:id,name'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where(
                    static fn (Builder $searchQuery) => $searchQuery
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas(
                            'insuranceCompany',
                            static fn (Builder $companyQuery) => $companyQuery
                                ->where('name', 'like', sprintf('%%%s%%', $search))
                        )
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('insurance-package/index', [
            'insurancePackages' => $insurancePackages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(InsurancePackage $insurancePackage): Response
    {
        $tenantId = (string) Auth::user()?->tenant_id;

        $insurancePackage->load(['insuranceCompany:id,name']);

        $prices = InsurancePackagePrice::query()
            ->where('insurance_package_id', $insurancePackage->id)
            ->with('branch:id,name')
            ->orderBy('billable_type')
            ->orderBy('effective_from')
            ->get();

        $serviceIds = $prices->where('billable_type', BillableItemType::SERVICE)->pluck('billable_id');
        $drugIds = $prices->where('billable_type', BillableItemType::DRUG)->pluck('billable_id');
        $testIds = $prices->where('billable_type', BillableItemType::TEST)->pluck('billable_id');

        $serviceNames = FacilityService::query()->whereIn('id', $serviceIds)->pluck('name', 'id');
        $drugNames = InventoryItem::query()->whereIn('id', $drugIds)->pluck('name', 'id');
        $testNames = LabTestCatalog::query()->whereIn('id', $testIds)->pluck('test_name', 'id');

        $pricesForView = $prices->map(static fn (InsurancePackagePrice $price): array => [
            'id' => $price->id,
            'facility_branch_id' => $price->facility_branch_id,
            'billable_type' => $price->billable_type->value,
            'billable_id' => $price->billable_id,
            'billable_name' => match ($price->billable_type) {
                BillableItemType::SERVICE => $serviceNames->get($price->billable_id, 'Unknown'),
                BillableItemType::DRUG => $drugNames->get($price->billable_id, 'Unknown'),
                BillableItemType::TEST => $testNames->get($price->billable_id, 'Unknown'),
                default => '-',
            },
            'price' => $price->price,
            'effective_from' => $price->effective_from?->toDateString(),
            'effective_to' => $price->effective_to?->toDateString(),
            'status' => $price->status->value,
            'branch' => $price->branch ? ['id' => $price->branch->id, 'name' => $price->branch->name] : null,
        ])->values();

        $serviceOptions = FacilityService::query()
            ->where('tenant_id', $tenantId)
            ->where('is_billable', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'service_code'])
            ->map(static fn (FacilityService $s): array => [
                'value' => $s->id,
                'label' => $s->name.($s->service_code !== '' ? ' ('.$s->service_code.')' : ''),
            ])
            ->values()
            ->all();

        $drugOptions = InventoryItem::query()
            ->where('tenant_id', $tenantId)
            ->where('item_type', InventoryItemType::DRUG)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name'])
            ->map(static fn (InventoryItem $d): array => [
                'value' => $d->id,
                'label' => $d->name.($d->generic_name !== '' ? ' ('.$d->generic_name.')' : ''),
            ])
            ->values()
            ->all();

        $testOptions = LabTestCatalog::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('test_name')
            ->get(['id', 'test_name', 'test_code'])
            ->map(static fn (LabTestCatalog $t): array => [
                'value' => $t->id,
                'label' => $t->test_name.($t->test_code !== '' ? ' ('.$t->test_code.')' : ''),
            ])
            ->values()
            ->all();

        $branches = FacilityBranch::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (FacilityBranch $b): array => ['value' => $b->id, 'label' => $b->name])
            ->values()
            ->all();

        return Inertia::render('insurance-package/show', [
            'insurancePackage' => $insurancePackage,
            'prices' => $pricesForView,
            'billableItems' => [
                'service' => $serviceOptions,
                'drug' => $drugOptions,
                'test' => $testOptions,
            ],
            'branches' => $branches,
        ]);
    }

    public function create(): Response
    {
        $companies = InsuranceCompany::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('insurance-package/create', [
            'companies' => $companies,
        ]);
    }

    public function store(StoreInsurancePackageRequest $request, CreateInsurancePackage $action): RedirectResponse
    {
        $action->handle($request->createDto());

        return to_route('insurance-packages.index')->with('success', 'Insurance package created successfully.');
    }

    public function edit(InsurancePackage $insurancePackage): Response
    {
        $companies = InsuranceCompany::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('insurance-package/edit', [
            'insurancePackage' => $insurancePackage,
            'companies' => $companies,
        ]);
    }

    public function update(
        UpdateInsurancePackageRequest $request,
        InsurancePackage $insurancePackage,
        UpdateInsurancePackage $action
    ): RedirectResponse {
        $action->handle($insurancePackage, $request->updateDto());

        return to_route('insurance-packages.index')->with('success', 'Insurance package updated successfully.');
    }

    public function destroy(
        DeleteInsurancePackageRequest $request,
        InsurancePackage $insurancePackage,
        DeleteInsurancePackage $action
    ): RedirectResponse {
        $action->handle($insurancePackage);

        return to_route('insurance-packages.index')->with('success', 'Insurance package deleted successfully.');
    }
}
