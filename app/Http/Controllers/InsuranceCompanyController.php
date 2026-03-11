<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsuranceCompany;
use App\Actions\DeleteInsuranceCompany;
use App\Actions\UpdateInsuranceCompany;
use App\Http\Requests\DeleteInsuranceCompanyRequest;
use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Http\Requests\UpdateInsuranceCompanyRequest;
use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InsuranceCompanyController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $insuranceCompanies = InsuranceCompany::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where(
                    static fn (Builder $searchQuery) => $searchQuery
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('main_contact', 'like', sprintf('%%%s%%', $search))
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('insurance-company/index', [
            'insuranceCompanies' => $insuranceCompanies,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('insurance-company/create', [
            'addresses' => \App\Models\Address::query()->select(['id', 'city', 'district'])->get(),
        ]);
    }

    public function store(StoreInsuranceCompanyRequest $request, CreateInsuranceCompany $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('insurance-companies.index')->with('success', 'Insurance company created successfully.');
    }

    public function edit(InsuranceCompany $insuranceCompany): Response
    {
        return Inertia::render('insurance-company/edit', [
            'insuranceCompany' => $insuranceCompany,
            'addresses' => \App\Models\Address::query()->select(['id', 'city', 'district'])->get(),
        ]);
    }

    public function update(
        UpdateInsuranceCompanyRequest $request,
        InsuranceCompany $insuranceCompany,
        UpdateInsuranceCompany $action
    ): RedirectResponse {
        $action->handle($insuranceCompany, $request->validated());

        return to_route('insurance-companies.index')->with('success', 'Insurance company updated successfully.');
    }

    public function destroy(
        DeleteInsuranceCompanyRequest $request,
        InsuranceCompany $insuranceCompany,
        DeleteInsuranceCompany $action
    ): RedirectResponse {
        $action->handle($insuranceCompany);

        return to_route('insurance-companies.index')->with('success', 'Insurance company deleted successfully.');
    }
}
