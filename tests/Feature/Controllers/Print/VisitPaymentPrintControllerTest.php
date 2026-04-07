<?php

declare(strict_types=1);

use App\Enums\BillingStatus;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\PayerType;
use App\Enums\StaffType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createVisitPaymentPrintContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Receipt Country '.$sequence,
        'country_code' => 'RC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Receipt Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Receipt Tenant '.$sequence,
        'domain' => 'receipt-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'RC'.$sequence,
        'name' => 'Receipt Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Receipt Branch '.$sequence,
        'branch_code' => 'RB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'RCPT-'.$sequence,
        'first_name' => 'Receipt',
        'last_name' => 'Clerk',
        'email' => 'receipt.clerk'.$sequence.'@test.com',
        'type' => StaffType::ADMINISTRATIVE,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'receipt.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-RC-'.$sequence,
        'first_name' => 'Receipt',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000501',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-RC-'.$sequence,
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $payer = VisitPayer::query()->create([
        'tenant_id' => $tenant->id,
        'patient_visit_id' => $visit->id,
        'billing_type' => PayerType::CASH,
    ]);

    $billing = VisitBilling::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'patient_visit_id' => $visit->id,
        'visit_payer_id' => $payer->id,
        'payer_type' => PayerType::CASH,
        'gross_amount' => 50000,
        'discount_amount' => 0,
        'paid_amount' => 20000,
        'balance_amount' => 30000,
        'status' => BillingStatus::PARTIAL_PAID,
        'billed_at' => now(),
    ]);

    $payment = Payment::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_billing_id' => $billing->id,
        'patient_visit_id' => $visit->id,
        'receipt_number' => 'RCT-'.$sequence,
        'payment_date' => now(),
        'amount' => 20000,
        'payment_method' => 'cash',
        'reference_number' => 'REF-'.$sequence,
        'is_refund' => false,
        'notes' => 'Front desk payment.',
    ]);

    $sequence++;

    return [$branch, $user, $visit, $payment];
}

it('streams a pdf for a visit payment receipt', function (): void {
    [$branch, $user, $visit, $payment] = createVisitPaymentPrintContext();

    $user->givePermissionTo('visits.view');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('visits.payments.print', [$visit, $payment]));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('does not allow printing a refund transaction as a receipt', function (): void {
    [$branch, $user, $visit, $payment] = createVisitPaymentPrintContext();

    $payment->forceFill(['is_refund' => true])->save();
    $user->givePermissionTo('visits.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('visits.payments.print', [$visit, $payment]))
        ->assertForbidden();
});
