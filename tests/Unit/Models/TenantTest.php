<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\CarbonImmutable;

test('current subscription resolves latest subscription by created_at', function (): void {
    $tenant = Tenant::factory()->create();
    $package = SubscriptionPackage::factory()->create();

    $older = TenantSubscription::query()->create([
        'tenant_id' => $tenant->id,
        'subscription_package_id' => $package->id,
        'status' => SubscriptionStatus::TRIAL,
        'created_at' => CarbonImmutable::parse('2026-01-01 00:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-01-01 00:00:00'),
    ]);

    $latest = TenantSubscription::query()->create([
        'tenant_id' => $tenant->id,
        'subscription_package_id' => $package->id,
        'status' => SubscriptionStatus::ACTIVE,
        'created_at' => CarbonImmutable::parse('2026-01-02 00:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-01-02 00:00:00'),
    ]);

    $tenant->refresh()->load('currentSubscription');

    expect($tenant->currentSubscription)->not->toBeNull()
        ->and($tenant->currentSubscription?->id)->toBe($latest->id)
        ->and($tenant->currentSubscription?->id)->not->toBe($older->id);
});
