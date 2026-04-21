<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantGeneralSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createAdministrationContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Admin Country '.$sequence,
        'country_code' => 'AC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Admin Package '.$sequence,
        'users' => 20,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Admin Tenant '.$sequence,
        'domain' => 'admin-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'UGX'.$sequence,
        'name' => 'Uganda Shilling '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin Branch '.$sequence,
        'branch_code' => 'ADB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'admin.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $sequence++;

    return [$tenant, $branch, $user, $currency];
}

it('shows the first release general settings page', function (): void {
    [, $branch, $user, $currency] = createAdministrationContext();

    $user->givePermissionTo([
        'currencies.view',
        'general_settings.view',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('administration.general-settings'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('administration/general-settings')
            ->has('sections', 4)
            ->where('values.require_payment_before_consultation', false)
            ->where('values.allow_insured_bypass_upfront_payment', true)
            ->where('values.enable_batch_tracking_when_dispensing', true)
            ->where('values.enforce_fefo', true)
            ->where('values.allow_partial_dispense', true)
            ->where('values.default_currency_id', null)
            ->where('currencies.0.value', $currency->id));
});

it('stores general settings for the current tenant', function (): void {
    [$tenant, $branch, $user, $currency] = createAdministrationContext();

    $user->givePermissionTo([
        'currencies.view',
        'general_settings.view',
        'general_settings.update',
    ]);

    $payload = [
        'require_payment_before_consultation' => true,
        'require_payment_before_laboratory' => true,
        'require_payment_before_pharmacy' => false,
        'require_payment_before_procedures' => true,
        'allow_insured_bypass_upfront_payment' => false,
        'default_currency_id' => $currency->id,
        'patient_number_prefix' => 'PT',
        'receipt_number_prefix' => 'RCT',
        'enable_batch_tracking_when_dispensing' => true,
        'enforce_fefo' => false,
        'allow_partial_dispense' => false,
        'require_review_before_lab_release' => true,
        'require_approval_before_lab_release' => true,
    ];

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->patch(route('administration.general-settings.update'), $payload);

    $response->assertRedirectToRoute('administration.general-settings');
    $response->assertSessionHas('success', 'General settings updated successfully.');

    expect(TenantGeneralSetting::query()
        ->where('tenant_id', $tenant->id)
        ->where('key', 'payments.require_payment_before_consultation')
        ->value('value'))->toBe('1')
        ->and(TenantGeneralSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'payments.allow_insured_bypass_upfront_payment')
            ->value('value'))->toBe('0')
        ->and(TenantGeneralSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'pricing.default_currency_id')
            ->value('value'))->toBe($currency->id)
        ->and(TenantGeneralSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'pharmacy.enforce_fefo')
            ->value('value'))->toBe('0')
        ->and(TenantGeneralSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'pharmacy.allow_partial_dispense')
            ->value('value'))->toBe('0');
});
