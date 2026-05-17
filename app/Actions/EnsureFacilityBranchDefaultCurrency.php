<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityBranch;
use App\Models\FacilityBranchCurrency;
use Illuminate\Support\Facades\Auth;

final readonly class EnsureFacilityBranchDefaultCurrency
{
    public function handle(FacilityBranch $branch): void
    {
        $tenantId = $branch->getAttribute('tenant_id');
        $currencyId = $branch->getAttribute('currency_id');

        if (! is_string($tenantId) || $tenantId === '' || ! is_string($currencyId) || $currencyId === '') {
            return;
        }

        $userId = Auth::id();

        FacilityBranchCurrency::query()->updateOrCreate(
            [
                'facility_branch_id' => $branch->id,
                'currency_id' => $currencyId,
            ],
            [
                'tenant_id' => $tenantId,
                'is_default' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ],
        );
    }
}
