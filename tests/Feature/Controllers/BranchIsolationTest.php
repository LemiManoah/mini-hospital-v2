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
use Illuminate\Support\Facades\Hash;

function createTenantWithBranches(int $count = 2): array
{
    $country = Country::query()->create([
        'country_name' => 'Uganda',
        'country_code' => 'UG',
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $currency = Currency::query()->create([
        'code' => 'UGX',
        'name' => 'Ugandan Shilling',
        'symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Starter Package',
        'users' => 2,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'City General Hospital',
        'domain' => 'city-general',
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
            'branch_code' => 'B'.$i,
            'currency_id' => $currency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => $i === 1,
            'has_store' => true,
        ]));
    }

    return [$tenant, $branches];
}

it('redirects tenant users to branch switcher when multiple branches and none selected', function (): void {
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

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirectToRoute('branch-switcher.index');
});

it('allows switching to an authorized branch and stores it in session', function (): void {
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
