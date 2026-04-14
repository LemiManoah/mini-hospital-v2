<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\StaffType;
use App\Enums\StockMovementType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Consultation;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantGeneralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createPharmacyModuleContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Pharmacy Country '.$sequence,
        'country_code' => 'PH'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Pharmacy Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Pharmacy Tenant '.$sequence,
        'domain' => 'pharmacy-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'PHC'.$sequence,
        'name' => 'Pharmacy Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pharmacy Branch '.$sequence,
        'branch_code' => 'PHB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $otherBranch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pharmacy Other Branch '.$sequence,
        'branch_code' => 'PHO'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => false,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'PHA-'.$sequence,
        'first_name' => 'Queue',
        'last_name' => 'Pharmacist',
        'email' => 'pharmacy.staff'.$sequence.'@test.com',
        'type' => StaffType::ALLIED_HEALTH,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'pharmacy.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->assignRole('pharmacist');

    $pharmacyLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Main Pharmacy '.$sequence,
        'location_code' => 'MPH'.$sequence,
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
        'is_dispensing_point' => true,
    ]);

    $pharmacyBackStore = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Pharmacy Back Store '.$sequence,
        'location_code' => 'PBS'.$sequence,
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
        'is_dispensing_point' => false,
    ]);

    $otherBranchPharmacy = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'name' => 'Other Branch Pharmacy '.$sequence,
        'location_code' => 'OBP'.$sequence,
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
        'is_dispensing_point' => true,
    ]);

    $readyDrug = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Amoxicillin '.$sequence,
        'generic_name' => 'Amoxicillin',
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $partialDrug = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metformin '.$sequence,
        'generic_name' => 'Metformin',
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $outOfStockDrug = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Salbutamol '.$sequence,
        'generic_name' => 'Salbutamol',
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $readyDrugBatch = InventoryBatch::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'inventory_item_id' => $readyDrug->id,
        'goods_receipt_item_id' => null,
        'batch_number' => 'RX-READY-'.$sequence,
        'expiry_date' => now()->addMonths(12)->toDateString(),
        'unit_cost' => 10,
        'quantity_received' => 30,
        'received_at' => now()->subDay(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $partialDrugBatch = InventoryBatch::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'inventory_item_id' => $partialDrug->id,
        'goods_receipt_item_id' => null,
        'batch_number' => 'RX-PARTIAL-'.$sequence,
        'expiry_date' => now()->addMonths(6)->toDateString(),
        'unit_cost' => 8,
        'quantity_received' => 2,
        'received_at' => now()->subDay(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    StockMovement::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'inventory_item_id' => $readyDrug->id,
        'inventory_batch_id' => $readyDrugBatch->id,
        'movement_type' => StockMovementType::Receipt,
        'quantity' => 30,
        'unit_cost' => 10,
        'occurred_at' => now()->subDay(),
        'created_by' => $user->id,
    ]);

    StockMovement::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'inventory_item_id' => $partialDrug->id,
        'inventory_batch_id' => $partialDrugBatch->id,
        'movement_type' => StockMovementType::Receipt,
        'quantity' => 2,
        'unit_cost' => 8,
        'occurred_at' => now()->subDay(),
        'created_by' => $user->id,
    ]);

    $sequence++;

    return [
        $branch,
        $otherBranch,
        $user,
        $staff,
        $pharmacyLocation,
        $pharmacyBackStore,
        $otherBranchPharmacy,
        $readyDrug,
        $partialDrug,
        $outOfStockDrug,
        $readyDrugBatch,
        $partialDrugBatch,
    ];
}

function createPharmacyPrescription(
    FacilityBranch $branch,
    Tenant $tenant,
    User $user,
    Staff $staff,
    array $items,
    PrescriptionStatus $status = PrescriptionStatus::PENDING,
    ?string $patientNameSuffix = null,
): Prescription {
    static $patientSequence = 1;

    $suffix = $patientNameSuffix ?? (string) $patientSequence;

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-PH-'.$suffix,
        'first_name' => 'Patient',
        'last_name' => 'Pharmacy '.$suffix,
        'gender' => 'female',
        'phone_number' => '+256700000'.$patientSequence,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-PH-'.$suffix,
        'visit_type' => VisitType::OUTPATIENT,
        'status' => VisitStatus::IN_PROGRESS,
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $consultation = Consultation::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_id' => $visit->id,
        'doctor_id' => $staff->id,
        'started_at' => now(),
        'primary_diagnosis' => 'Pharmacy diagnosis '.$suffix,
    ]);

    $prescription = Prescription::query()->create([
        'visit_id' => $visit->id,
        'consultation_id' => $consultation->id,
        'prescribed_by' => $staff->id,
        'prescription_date' => now(),
        'primary_diagnosis' => 'Pharmacy diagnosis '.$suffix,
        'pharmacy_notes' => 'Review and prepare the medications.',
        'status' => $status,
    ]);

    foreach ($items as $item) {
        PrescriptionItem::query()->create([
            'prescription_id' => $prescription->id,
            'inventory_item_id' => $item['inventory_item_id'],
            'dosage' => $item['dosage'] ?? '1 tablet',
            'frequency' => $item['frequency'] ?? 'BD',
            'route' => $item['route'] ?? 'oral',
            'duration_days' => $item['duration_days'] ?? 5,
            'quantity' => $item['quantity'],
            'instructions' => $item['instructions'] ?? 'Take after meals.',
            'is_prn' => false,
            'is_external_pharmacy' => false,
            'status' => $item['status'] ?? PrescriptionItemStatus::PENDING,
            'dispensed_at' => $item['dispensed_at'] ?? null,
        ]);
    }

    $patientSequence++;

    return $prescription->fresh(['visit.patient', 'items.inventoryItem', 'prescribedBy']);
}

function storePharmacyGeneralSetting(Tenant $tenant, string $key, string $value): void
{
    TenantGeneralSetting::query()->updateOrCreate(
        ['tenant_id' => $tenant->id, 'key' => $key],
        ['value' => $value],
    );
}
