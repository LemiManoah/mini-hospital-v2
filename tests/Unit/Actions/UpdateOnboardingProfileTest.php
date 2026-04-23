<?php

declare(strict_types=1);

use App\Actions\UpdateOnboardingProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('updates the onboarding profile using the selected address model', function (): void {
    $tenantId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $existingAddressId = (string) Str::uuid();
    $newAddressId = (string) Str::uuid();

    seedTenantContext($tenantId);

    DB::table('countries')->insert([
        [
            'id' => 'country-old',
            'country_name' => 'Oldland',
            'country_code' => 'OL',
            'dial_code' => '+256',
            'currency' => 'UGX',
            'currency_symbol' => 'USh',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 'country-new',
            'country_name' => 'Newland',
            'country_code' => 'NL',
            'dial_code' => '+256',
            'currency' => 'UGX',
            'currency_symbol' => 'USh',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('addresses')->insert([
        [
            'id' => $existingAddressId,
            'country_id' => 'country-old',
            'city' => 'Kampala',
            'district' => 'Central',
            'state' => 'Kampala',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $newAddressId,
            'country_id' => 'country-new',
            'city' => 'Entebbe',
            'district' => 'Wakiso',
            'state' => 'Central',
            'created_at' => now(),
            'updated_at' => now(),
        ],
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

    DB::table('tenants')->where('id', $tenantId)->update([
        'address_id' => $existingAddressId,
        'country_id' => 'country-old',
    ]);

    $tenant = Tenant::query()->findOrFail($tenantId);
    $user = User::query()->findOrFail($userId);

    $this->actingAs($user);

    $updated = resolve(UpdateOnboardingProfile::class)->handle($tenant, [
        'name' => 'Updated Facility',
        'domain' => '',
        'facility_level' => 'hospital',
        'country_id' => 'country-old',
        'address_id' => $newAddressId,
    ]);

    expect($updated->id)->toBe($tenantId)
        ->and($updated->name)->toBe('Updated Facility')
        ->and($updated->domain)->toBeNull()
        ->and($updated->country_id)->toBe('country-new')
        ->and($updated->address_id)->toBe($newAddressId)
        ->and($updated->onboarding_current_step)->toBe('branch')
        ->and($updated->updated_by)->toBe($userId);
});
