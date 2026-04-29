<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class EnsureBranchPaymentMethods
{
    /**
     * @return Collection<int, PaymentMethod>
     */
    public function handle(string $tenantId, ?string $branchId): Collection
    {
        $query = PaymentMethod::query()
            ->where('tenant_id', $tenantId)
            ->where('facility_branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($query->exists()) {
            return $query->get();
        }

        $now = now();
        $userId = Auth::id();

        $defaults = [
            ['code' => 'cash', 'name' => 'Cash', 'type' => 'cash', 'requires_reference' => false, 'sort_order' => 10],
            ['code' => 'card', 'name' => 'Card', 'type' => 'card', 'requires_reference' => true, 'sort_order' => 20],
            ['code' => 'mobile_money', 'name' => 'Mobile Money', 'type' => 'mobile_money', 'requires_reference' => true, 'sort_order' => 30],
            ['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'type' => 'bank_transfer', 'requires_reference' => true, 'sort_order' => 40],
        ];

        foreach ($defaults as $default) {
            PaymentMethod::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'facility_branch_id' => $branchId,
                    'code' => $default['code'],
                ],
                [
                    'name' => $default['name'],
                    'type' => $default['type'],
                    'requires_reference' => $default['requires_reference'],
                    'is_active' => true,
                    'sort_order' => $default['sort_order'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        return $query->get();
    }
}
