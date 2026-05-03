<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsurancePackagePrice;
use App\Actions\DeleteInsurancePackagePrice;
use App\Actions\UpdateInsurancePackagePrice;
use App\Http\Requests\StoreInsurancePackagePriceRequest;
use App\Http\Requests\UpdateInsurancePackagePriceRequest;
use App\Models\InsurancePackage;
use App\Models\InsurancePackagePrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class InsurancePackagePriceController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.update', only: ['store', 'update', 'destroy']),
        ];
    }

    public function store(
        StoreInsurancePackagePriceRequest $request,
        InsurancePackage $insurancePackage,
        CreateInsurancePackagePrice $action
    ): RedirectResponse {
        $action->handle($insurancePackage, $request->validated());

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Price added successfully.');
    }

    public function update(
        UpdateInsurancePackagePriceRequest $request,
        InsurancePackage $insurancePackage,
        InsurancePackagePrice $price,
        UpdateInsurancePackagePrice $action
    ): RedirectResponse {
        $action->handle($price, $request->validated());

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Price updated successfully.');
    }

    public function destroy(
        InsurancePackage $insurancePackage,
        InsurancePackagePrice $price,
        DeleteInsurancePackagePrice $action
    ): RedirectResponse {
        $action->handle($price);

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Price removed successfully.');
    }
}
