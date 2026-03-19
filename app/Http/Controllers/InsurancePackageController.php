<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsurancePackage;
use App\Actions\DeleteInsurancePackage;
use App\Actions\UpdateInsurancePackage;
use App\Http\Requests\DeleteInsurancePackageRequest;
use App\Http\Requests\StoreInsurancePackageRequest;
use App\Http\Requests\UpdateInsurancePackageRequest;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InsurancePackageController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.view', only: ['index']),
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
        $action->handle($request->validated());

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
        $action->handle($insurancePackage, $request->validated());

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
