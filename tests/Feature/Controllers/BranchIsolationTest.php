<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createTenantWithBranches(int $count = 2): array
{
    static $sequence = 1;
    $suffix = Str::lower(Str::random(6));

    $country = Country::query()->create([
        'country_name' => 'Uganda '.$suffix,
        'country_code' => 'CT'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $currency = Currency::query()->create([
        'code' => 'C'.mb_str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
        'name' => 'Currency '.$suffix,
        'symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Starter Package '.$suffix,
        'users' => $sequence + 1,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'City General Hospital '.$suffix,
        'domain' => 'city-general-'.$suffix,
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'country_id' => $country->id,
    ]);

    $branches = collect();
    for ($i = 1; $i <= $count; $i++) {
        $branches->push(FacilityBranch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Branch '.$i,
            'branch_code' => mb_strtoupper(mb_substr($suffix, 0, 3)).$i,
            'currency_id' => $currency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => $i === 1,
            'has_store' => true,
        ]));
    }

    $sequence++;

    return [$tenant, $branches];
}

it('redirects tenant users to branch switcher when multiple branches and none selected', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'MED-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@hospital.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'john.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo('facility_branches.view');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirectToRoute('branch-switcher.index');
});

it('allows switching to an authorized branch and stores it in session', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'NUR-001',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@hospital.com',
        'type' => StaffType::NURSING,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'jane.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['facility_branches.view', 'facility_branches.update']);

    $response = $this->actingAs($user)->post(route('branch-switcher.switch', $branches[0]->id));

    $response->assertRedirectToRoute('dashboard');
    $response->assertSessionHas('active_branch_id', $branches[0]->id);
});

it('forbids non-support users from opening the facility switcher', function (): void {
    $user = User::query()->create([
        'email' => 'plain.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $response = $this->actingAs($user)->get(route('facility-switcher.index'));

    $response->assertForbidden();
});

it('forbids non-support users from switching facility context', function (): void {
    [$tenant] = createTenantWithBranches();

    $user = User::query()->create([
        'email' => 'plain.switch.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $response = $this->actingAs($user)->post(route('facility-switcher.switch', $tenant->id));

    $response->assertForbidden();
});

it('allows support users to switch tenant context and clears active branch selection', function (): void {
    $this->seed(PermissionSeeder::class);

    [$sourceTenant, $branches] = createTenantWithBranches();
    [$targetTenant] = createTenantWithBranches();

    $supportUser = User::query()->create([
        'tenant_id' => $sourceTenant->id,
        'email' => 'support.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => true,
    ]);
    $supportUser->forceFill(['email_verified_at' => now()])->save();
    $supportUser->givePermissionTo('tenants.update');

    $response = $this
        ->withSession(['active_branch_id' => $branches[0]->id])
        ->actingAs($supportUser)
        ->post(route('facility-switcher.switch', $targetTenant->id));

    $response->assertRedirectToRoute('branch-switcher.index');
    $response->assertSessionMissing('active_branch_id');
    $response->assertSessionHas('success', 'Switched to '.$targetTenant->name);

    $this->assertDatabaseHas('users', [
        'id' => $supportUser->id,
        'tenant_id' => $targetTenant->id,
    ]);
});
