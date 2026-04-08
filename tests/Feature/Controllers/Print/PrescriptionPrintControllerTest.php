<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\StaffType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Consultation;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createPrescriptionPrintContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Prescription Print Country '.$sequence,
        'country_code' => 'PP'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Prescription Print Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Prescription Print Tenant '.$sequence,
        'domain' => 'prescription-print-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'PP'.$sequence,
        'name' => 'Prescription Print Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Prescription Print Branch '.$sequence,
        'branch_code' => 'PPB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'RX-PRINT-'.$sequence,
        'first_name' => 'Print',
        'last_name' => 'Doctor',
        'email' => 'prescription.print'.$sequence.'@test.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'prescription.print.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-PP-'.$sequence,
        'first_name' => 'Prescription',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000701',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-PP-'.$sequence,
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
        'primary_diagnosis' => 'Upper respiratory tract infection',
    ]);

    $drug = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Amoxicillin 500mg Capsules',
        'generic_name' => 'Amoxicillin',
        'brand_name' => 'Amoxil',
        'item_type' => InventoryItemType::DRUG,
        'strength' => '500mg',
        'default_selling_price' => 1500,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $prescription = Prescription::query()->create([
        'visit_id' => $visit->id,
        'consultation_id' => $consultation->id,
        'prescribed_by' => $staff->id,
        'prescription_date' => now(),
        'primary_diagnosis' => 'Upper respiratory tract infection',
        'pharmacy_notes' => 'Dispense full course and counsel on adherence.',
        'status' => PrescriptionStatus::PENDING,
    ]);

    PrescriptionItem::query()->create([
        'prescription_id' => $prescription->id,
        'inventory_item_id' => $drug->id,
        'dosage' => '1 capsule',
        'frequency' => 'TDS',
        'route' => 'oral',
        'duration_days' => 5,
        'quantity' => 15,
        'instructions' => 'Take after meals.',
        'is_prn' => false,
        'is_external_pharmacy' => false,
        'status' => PrescriptionItemStatus::PENDING,
    ]);

    $sequence++;

    return [$branch, $user, $prescription];
}

it('streams a pdf for a prescription', function (): void {
    [$branch, $user, $prescription] = createPrescriptionPrintContext();

    $user->givePermissionTo('visits.view');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('prescriptions.print', $prescription));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('does not allow printing a prescription from another active branch', function (): void {
    [$branch, $user, $prescription] = createPrescriptionPrintContext();

    $otherBranch = FacilityBranch::query()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Prescription Print Other Branch',
        'branch_code' => 'PPOB',
        'currency_id' => $branch->currency_id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => false,
        'has_store' => true,
    ]);

    $user->staff?->branches()->syncWithoutDetaching([
        $otherBranch->id => ['is_primary_location' => false],
    ]);

    $user->givePermissionTo('visits.view');

    $this->withSession(['active_branch_id' => $otherBranch->id])
        ->actingAs($user)
        ->get(route('prescriptions.print', $prescription))
        ->assertForbidden();
});
