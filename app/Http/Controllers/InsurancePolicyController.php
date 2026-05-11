<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInsurancePolicy;
use App\Actions\DeleteInsurancePolicy;
use App\Actions\UpdateInsurancePolicy;
use App\Http\Requests\StoreInsurancePolicyRequest;
use App\Http\Requests\UpdateInsurancePolicyRequest;
use App\Models\FacilityBranch;
use App\Models\InsurancePackage;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class InsurancePolicyController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:insurance_packages.update'),
        ];
    }

    public function store(
        StoreInsurancePolicyRequest $request,
        InsurancePackage $insurancePackage,
        CreateInsurancePolicy $action,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $activeBranch = BranchContext::getActiveBranch($user);

        if (! $activeBranch instanceof FacilityBranch) {
            return back()->withErrors([
                'branch' => 'Please select an active branch before creating an insurance policy.',
            ]);
        }

        $action->handle($insurancePackage, $request->policyData($activeBranch->id));

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Insurance policy created successfully.');
    }

    public function update(
        UpdateInsurancePolicyRequest $request,
        InsurancePackage $insurancePackage,
        InsurancePolicy $insurancePolicy,
        UpdateInsurancePolicy $action,
    ): RedirectResponse {
        $this->ensurePolicyBelongsToPackage($insurancePackage, $insurancePolicy);

        $action->handle($insurancePolicy, $request->policyData());

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Insurance policy updated successfully.');
    }

    public function destroy(
        InsurancePackage $insurancePackage,
        InsurancePolicy $insurancePolicy,
        DeleteInsurancePolicy $action,
    ): RedirectResponse {
        $this->ensurePolicyBelongsToPackage($insurancePackage, $insurancePolicy);

        $action->handle($insurancePolicy);

        return to_route('insurance-packages.show', $insurancePackage)
            ->with('success', 'Insurance policy removed successfully.');
    }

    private function ensurePolicyBelongsToPackage(InsurancePackage $insurancePackage, InsurancePolicy $policy): void
    {
        abort_if($policy->insurance_package_id !== $insurancePackage->id, 404);
    }
}
