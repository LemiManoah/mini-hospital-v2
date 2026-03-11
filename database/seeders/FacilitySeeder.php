<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Models\Address;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get dependencies
        $package = SubscriptionPackage::query()->first();
        $uganda = Country::query()->where('country_code', 'UG')->first();
        $kenya = Country::query()->where('country_code', 'KE')->first();
        $ugxCurrency = Currency::query()->where('code', 'UGX')->first();
        $kesCurrency = Currency::query()->where('code', 'KES')->first();

        if (! $package || ! $uganda || ! $kenya || ! $ugxCurrency || ! $kesCurrency) {
            return;
        }

        // Create addresses for facilities
        $kampalaAddress = Address::query()->firstOrCreate([
            'city' => 'Kampala',
            'district' => 'Kampala Central',
            'state' => 'Central',
            'country_id' => $uganda->id,

        ]);

        $entebbeAddress = Address::query()->firstOrCreate([
            'city' => 'Entebbe',
            'district' => 'Wakiso',
            'state' => 'Central',
            'country_id' => $uganda->id,
        ]);

        $mukonoAddress = Address::query()->firstOrCreate([
            'city' => 'Mukono',
            'district' => 'Mukono',
            'state' => 'Central',
            'country_id' => $uganda->id,
        ]);

        $nairobiAddress = Address::query()->firstOrCreate([
            'city' => 'Nairobi',
            'district' => 'Nairobi County',
            'state' => 'Nairobi',
            'country_id' => $kenya->id,
        ]);

        $mombasaAddress = Address::query()->firstOrCreate([
            'city' => 'Mombasa',
            'district' => 'Mombasa County',
            'state' => 'Coast',
            'country_id' => $kenya->id,
        ]);

        // Facility 1: Multi-branch Hospital (has_branches = true)
        $multiBranchTenant = Tenant::query()->firstOrCreate([
            'name' => 'City General Hospital',
            'domain' => 'citygeneral',
        ], [
            'has_branches' => true,
            'subscription_package_id' => $package->id,
            'status' => GeneralStatus::ACTIVE,
            'country_id' => $uganda->id,
            'address_id' => $kampalaAddress->id,
            'facility_level' => FacilityLevel::HOSPITAL->value,
            'longitude' => 32.5726,
            'latitude' => 0.3166,
        ]);

        // Main branch for multi-branch facility
        FacilityBranch::query()->firstOrCreate([
            'tenant_id' => $multiBranchTenant->id,
            'branch_code' => 'CGH-MAIN',
        ], [
            'name' => 'City General Hospital - Main Branch',
            'address_id' => $kampalaAddress->id,
            'currency_id' => $ugxCurrency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => true,
            'has_store' => true,
            'main_contact' => '+256 414 123456',
            'other_contact' => '+256 414 123457',
            'email' => 'main@citygeneral.ug',
        ]);

        // Branch 1 for multi-branch facility
        FacilityBranch::query()->firstOrCreate([
            'tenant_id' => $multiBranchTenant->id,
            'branch_code' => 'CGH-ENT',
        ], [
            'name' => 'City General Hospital - Entebbe Branch',
            'address_id' => $entebbeAddress->id,
            'currency_id' => $ugxCurrency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => false,
            'has_store' => true,
            'main_contact' => '+256 414 234567',
            'other_contact' => '+256 414 234568',
            'email' => 'entebbe@citygeneral.ug',
        ]);

        // Branch 2 for multi-branch facility
        FacilityBranch::query()->firstOrCreate([
            'tenant_id' => $multiBranchTenant->id,
            'branch_code' => 'CGH-MUK',
        ], [
            'name' => 'City General Hospital - Mukono Branch',
            'address_id' => $mukonoAddress->id,
            'currency_id' => $ugxCurrency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => false,
            'has_store' => false,
            'main_contact' => '+256 414 345678',
            'other_contact' => '+256 414 345679',
            'email' => 'mukono@citygeneral.ug',
        ]);

        // Facility 2: Single-branch Clinic (has_branches = false)
        $singleBranchTenant = Tenant::query()->firstOrCreate([
            'name' => 'Nairobi Medical Center',
            'domain' => 'nairoimedical',
        ], [
            'has_branches' => false,
            'subscription_package_id' => $package->id,
            'status' => GeneralStatus::ACTIVE,
            'country_id' => $kenya->id,
            'address_id' => $nairobiAddress->id,
            'facility_level' => FacilityLevel::HEALTH_CENTER_III->value,
            'longitude' => 36.8219,
            'latitude' => -1.2921,
        ]);

        // Single branch facility (main branch)
        FacilityBranch::query()->firstOrCreate([
            'tenant_id' => $singleBranchTenant->id,
            'branch_code' => 'NMC-MAIN',
        ], [
            'name' => 'Nairobi Medical Center',
            'address_id' => $nairobiAddress->id,
            'currency_id' => $kesCurrency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => true,
            'has_store' => true,
            'main_contact' => '+254 20 123456',
            'other_contact' => '+254 20 123457',
            'email' => 'info@nairoimedical.ke',

        ]);

        // Facility 3: Another multi-branch facility (has_branches = true)
        $multiBranchTenant2 = Tenant::query()->firstOrCreate([
            'name' => 'Coastal Healthcare Group',
            'domain' => 'coastalhealth',
        ], [
            'has_branches' => true,
            'subscription_package_id' => $package->id,
            'status' => GeneralStatus::ACTIVE,
            'country_id' => $kenya->id,
            'address_id' => $mombasaAddress->id,
            'facility_level' => FacilityLevel::HEALTH_CENTER_II->value,
            'longitude' => 39.6682,
            'latitude' => -4.0435,
        ]);

        // Main branch for second multi-branch facility
        FacilityBranch::query()->firstOrCreate([
            'tenant_id' => $multiBranchTenant2->id,
            'branch_code' => 'CHG-MOM',
        ], [
            'name' => 'Coastal Healthcare - Mombasa Main',
            'address_id' => $mombasaAddress->id,
            'currency_id' => $kesCurrency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => true,
            'has_store' => true,
            'main_contact' => '+254 41 234567',
            'other_contact' => '+254 41 234568',
            'email' => 'info@coastalhealth.ke',
        ]);

        // Branch for second multi-branch facility
        FacilityBranch::query()->firstOrCreate([
            'tenant_id' => $multiBranchTenant2->id,
            'branch_code' => 'CHG-DIANI',
        ], [
            'name' => 'Coastal Healthcare - Diani Branch',
            'address_id' => $mombasaAddress->id, // Using same city for simplicity
            'currency_id' => $kesCurrency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => false,
            'has_store' => true,
            'main_contact' => '+254 41 345678',
            'other_contact' => '+254 41 345679',
            'email' => 'diani@coastalhealth.ke',
        ]);
    }
}
