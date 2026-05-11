<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsurancePolicyItem;
use App\Actions\DeleteInsurancePolicyItem;
use App\Actions\UpdateInsurancePolicyItem;
use App\Http\Requests\StoreInsurancePolicyItemRequest;
use App\Http\Requests\UpdateInsurancePolicyItemRequest;
use App\Models\InsurancePackage;
use App\Models\InsurancePolicy;
use App\Models\InsurancePolicyItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class InsurancePolicyItemController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.update'),
        ];
    }

    public function store(
        StoreInsurancePolicyItemRequest $request,
        InsurancePackage $insurancePackage,
        InsurancePolicy $insurancePolicy,
        CreateInsurancePolicyItem $action,
    ): RedirectResponse {
        $this->ensurePolicyBelongsToPackage($insurancePackage, $insurancePolicy);

        $action->handle($insurancePolicy, $request->itemData());

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Policy item added successfully.');
    }

    public function update(
        UpdateInsurancePolicyItemRequest $request,
        InsurancePackage $insurancePackage,
        InsurancePolicy $insurancePolicy,
        InsurancePolicyItem $insurancePolicyItem,
        UpdateInsurancePolicyItem $action,
    ): RedirectResponse {
        $this->ensurePolicyItemBelongsToPackage($insurancePackage, $insurancePolicy, $insurancePolicyItem);

        $action->handle($insurancePolicyItem, $request->itemData());

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Policy item updated successfully.');
    }

    public function destroy(
        InsurancePackage $insurancePackage,
        InsurancePolicy $insurancePolicy,
        InsurancePolicyItem $insurancePolicyItem,
        DeleteInsurancePolicyItem $action,
    ): RedirectResponse {
        $this->ensurePolicyItemBelongsToPackage($insurancePackage, $insurancePolicy, $insurancePolicyItem);

        $action->handle($insurancePolicyItem);

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Policy item removed successfully.');
    }

    private function ensurePolicyBelongsToPackage(InsurancePackage $insurancePackage, InsurancePolicy $policy): void
    {
        abort_if($policy->insurance_package_id !== $insurancePackage->id, 404);
    }

    private function ensurePolicyItemBelongsToPackage(
        InsurancePackage $insurancePackage,
        InsurancePolicy $policy,
        InsurancePolicyItem $item,
    ): void {
        $this->ensurePolicyBelongsToPackage($insurancePackage, $policy);

        abort_if($item->insurance_policy_id !== $policy->id, 404);
    }
}
