<?php

declare(strict_types=1);

use App\Actions\EnsureBranchPaymentMethods;
use App\Models\FacilityBranch;
use App\Models\PaymentMethod;
use App\Models\Tenant;

it('creates default active payment methods for a branch when none exist', function (): void {
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $methods = resolve(EnsureBranchPaymentMethods::class)->handle($tenant->id, $branch->id);

    expect($methods)->toHaveCount(4)
        ->and($methods->pluck('code')->all())->toBe([
            'cash',
            'card',
            'mobile_money',
            'bank_transfer',
        ])
        ->and(PaymentMethod::query()
            ->where('tenant_id', $tenant->id)
            ->where('facility_branch_id', $branch->id)
            ->count())->toBe(4);
});
