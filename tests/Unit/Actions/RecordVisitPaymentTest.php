<?php

declare(strict_types=1);

use App\Actions\RecordVisitPayment;
use App\Data\Patient\CreateVisitPaymentDTO;
use App\Enums\GeneralStatus;
use App\Enums\PayerType;
use App\Models\PatientVisit;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

it('records a visit payment from a typed dto and recalculates billing', function (): void {
    $tenantId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $subscriptionPackageId = (string) Str::uuid();
    $currencyId = (string) Str::uuid();

    DB::table('subscription_packages')->insert([
        'id' => $subscriptionPackageId,
        'name' => 'Payment Test Package',
        'users' => 100,
        'price' => 0,
        'status' => GeneralStatus::ACTIVE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('currencies')->insert([
        'id' => $currencyId,
        'code' => 'PTX',
        'name' => 'Payment Test Currency',
        'symbol' => 'P$',
        'modifiable' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tenants')->insert([
        'id' => $tenantId,
        'name' => 'Payment Test Tenant',
        'domain' => 'payment-test-tenant',
        'has_branches' => true,
        'subscription_package_id' => $subscriptionPackageId,
        'status' => GeneralStatus::ACTIVE->value,
        'facility_level' => 'hospital',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patients')->insert([
        'id' => $patientId,
        'tenant_id' => $tenantId,
        'patient_number' => 'PAT-200',
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'gender' => 'male',
        'phone_number' => '+256700000000',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('facility_branches')->insert([
        'id' => $branchId,
        'name' => 'Main Branch',
        'branch_code' => 'BR-PAY',
        'tenant_id' => $tenantId,
        'currency_id' => $currencyId,
        'status' => GeneralStatus::ACTIVE->value,
        'is_main_branch' => true,
        'has_store' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-200',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_payers')->insert([
        'id' => $payerId,
        'tenant_id' => $tenantId,
        'patient_visit_id' => $visitId,
        'billing_type' => PayerType::CASH->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'patient_visit_id' => $visitId,
        'visit_payer_id' => $payerId,
        'payer_type' => PayerType::CASH->value,
        'gross_amount' => 0,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => 0,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_charges')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visitId,
        'source_type' => 'manual',
        'source_id' => (string) Str::uuid(),
        'description' => 'Consultation fee',
        'quantity' => 1,
        'unit_price' => 75,
        'line_total' => 75,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    actingAs($user);

    $payment = resolve(RecordVisitPayment::class)->handle(
        PatientVisit::query()->findOrFail($visitId),
        new CreateVisitPaymentDTO(
            amount: 40.0,
            paymentMethod: 'cash',
            paymentDate: '2026-04-23',
            referenceNumber: 'RCT-200',
            notes: 'Partial settlement',
        ),
    );

    $billing = VisitBilling::query()->findOrFail($billingId);

    expect((float) $payment->amount)->toBe(40.0)
        ->and($payment->payment_method)->toBe('cash')
        ->and($payment->reference_number)->toBe('RCT-200')
        ->and((float) $billing->paid_amount)->toBe(40.0)
        ->and((float) $billing->balance_amount)->toBe(35.0);
});
