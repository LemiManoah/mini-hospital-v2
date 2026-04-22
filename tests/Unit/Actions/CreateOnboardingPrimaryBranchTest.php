<?php

declare(strict_types=1);

use App\Actions\CreateOnboardingPrimaryBranch;
use App\Data\Onboarding\CreateOnboardingPrimaryBranchDTO;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('creates the onboarding primary branch using a typed dto', function (): void {
    $tenantId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $addressId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);

    DB::table('countries')->insert([
        'id' => 'country-main',
        'country_name' => 'Testland',
        'country_code' => 'TL',
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('addresses')->insert([
        'id' => $addressId,
        'country_id' => 'country-main',
        'city' => 'Kampala',
        'district' => 'Central',
        'state' => 'Kampala',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users')->insert([
        'id' => $userId,
        'tenant_id' => $tenantId,
        'email' => 'owner@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::query()->findOrFail($tenantId);
    $user = User::query()->findOrFail($userId);

    $dto = new CreateOnboardingPrimaryBranchDTO(
        name: 'Main Branch',
        branchCode: 'MB-01',
        email: 'branch@example.com',
        mainContact: '+256700000001',
        otherContact: null,
        currencyId: $tenantContext['currency_id'],
        addressId: $addressId,
        countryId: 'country-main',
        hasStore: true,
    );

    $branch = resolve(CreateOnboardingPrimaryBranch::class)->handle($tenant, $user, $dto);

    expect($branch->name)->toBe('Main Branch')
        ->and($branch->branch_code)->toBe('MB-01')
        ->and($branch->email)->toBe('branch@example.com')
        ->and($branch->has_store)->toBeTrue()
        ->and($tenant->fresh()->onboarding_current_step)->toBe('departments')
        ->and($tenant->fresh()->country_id)->toBe('country-main');
});
