<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use App\Enums\PayerType;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createFinancePaymentContext(): array
{
    $tenant = Tenant::factory()->create([
        'status' => GeneralStatus::ACTIVE,
        'onboarding_completed_at' => now(),
    ]);

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-FIN-001',
        'first_name' => 'Martha',
        'last_name' => 'Cash',
        'gender' => 'female',
        'phone_number' => '+256700111111',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-FIN-001',
        'visit_type' => 'opd_consultation',
        'status' => 'in_progress',
        'is_emergency' => false,
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $paymentMethodId = (string) Str::uuid();

    DB::table('visit_payers')->insert([
        'id' => $payerId,
        'tenant_id' => $tenant->id,
        'patient_visit_id' => $visit->id,
        'billing_type' => PayerType::CASH->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'patient_visit_id' => $visit->id,
        'visit_payer_id' => $payerId,
        'payer_type' => PayerType::CASH->value,
        'gross_amount' => 150,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => 150,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_charges')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visit->id,
        'source_type' => 'manual',
        'source_id' => (string) Str::uuid(),
        'description' => 'Consultation fee',
        'quantity' => 1,
        'unit_price' => 150,
        'line_total' => 150,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('payment_methods')->insert([
        'id' => $paymentMethodId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'code' => 'cash',
        'name' => 'Cash',
        'type' => 'cash',
        'requires_reference' => false,
        'is_active' => true,
        'sort_order' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$tenant, $branch, $user, $visit, $paymentMethodId];
}

it('keeps the visit profile read-only for billing context', function (): void {
    [, $branch, $user, $visit] = createFinancePaymentContext();
    $user->givePermissionTo('visits.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('visit/show')
            ->missing('paymentMethods'));
});

it('shows payable opd visits in the finance queue', function (): void {
    [, $branch, $user] = createFinancePaymentContext();
    $user->givePermissionTo('payments.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('finance.opd-payments.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/opd-payments/index')
            ->where('visits.data.0.visit_number', 'VIS-FIN-001')
            ->where('visits.data.0.billing.balance_amount', 150));
});

it('exposes insurance copay splits for the finance cashiering flow', function (): void {
    [$tenant, $branch, $user, $visit] = createFinancePaymentContext();
    $insuranceCompanyId = (string) Str::uuid();
    $insurancePackageId = (string) Str::uuid();

    seedInsuranceCoverage($tenant->id, $insuranceCompanyId, $insurancePackageId);

    DB::table('visit_payers')
        ->where('patient_visit_id', $visit->id)
        ->update([
            'billing_type' => PayerType::INSURANCE->value,
            'insurance_company_id' => $insuranceCompanyId,
            'insurance_package_id' => $insurancePackageId,
        ]);

    DB::table('visit_billings')
        ->where('patient_visit_id', $visit->id)
        ->update([
            'payer_type' => PayerType::INSURANCE->value,
            'insurance_company_id' => $insuranceCompanyId,
            'insurance_package_id' => $insurancePackageId,
            'status' => 'insurance_pending',
        ]);

    DB::table('visit_charges')
        ->where('patient_visit_id', $visit->id)
        ->update(['copay_amount' => 50]);

    $user->givePermissionTo('payments.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('finance.opd-payments.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/opd-payments/index')
            ->where('visits.data.0.payer.billing_type', 'insurance')
            ->where('visits.data.0.payer.insurance_company_name', fn (string $name): bool => str_starts_with($name, 'Test Insurance '))
            ->where('visits.data.0.payer.insurance_package_name', fn (string $name): bool => str_starts_with($name, 'Test Cover '))
            ->where('visits.data.0.billing.split.patient_responsibility_amount', 50.0)
            ->where('visits.data.0.billing.split.patient_balance_amount', 50.0)
            ->where('visits.data.0.billing.split.insurer_responsibility_amount', 100.0));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('finance.opd-payments.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/opd-payments/show')
            ->where('visit.billing.split.patient_balance_amount', 50.0)
            ->where('visit.billing.split.insurer_balance_amount', 100.0)
            ->where('visit.charges.0.copay_amount', '50.00'));
});

it('records payments from the finance opd payment desk', function (): void {
    [, $branch, $user, $visit, $paymentMethodId] = createFinancePaymentContext();
    $user->givePermissionTo(['payments.view', 'payments.create']);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('finance.opd-payments.store', $visit), [
            'amount' => 60,
            'payment_method_id' => $paymentMethodId,
            'payment_date' => '2026-04-28 10:30:00',
            'reference_number' => 'FIN-OPD-001',
            'notes' => 'Collected at finance desk',
        ])
        ->assertRedirect(route('finance.opd-payments.show', $visit))
        ->assertSessionHas('success', 'Payment recorded successfully.');

    $this->assertDatabaseHas('payments', [
        'patient_visit_id' => $visit->id,
        'payment_method_id' => $paymentMethodId,
        'payment_method' => 'cash',
        'amount' => '60.00',
    ]);

    $this->assertDatabaseHas('visit_billings', [
        'patient_visit_id' => $visit->id,
        'paid_amount' => '60.00',
        'balance_amount' => '90.00',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'tenant_id' => $visit->tenant_id,
        'branch_id' => $visit->facility_branch_id,
        'log_name' => 'billing',
        'event' => 'payment.recorded',
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('finance.opd-payments.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/opd-payments/show')
            ->has('audit_activity', 1)
            ->where('audit_activity.0.title', 'Payment recorded for patient visit.'));
});

it('requests approves and reverses a billing discount from the finance desk', function (): void {
    [, $branch, $user, $visit] = createFinancePaymentContext();
    $user->givePermissionTo([
        'payments.view',
        'billing_discounts.create',
        'billing_discounts.approve',
        'billing_discounts.reverse',
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('finance.opd-payments.discounts.store', $visit), [
            'amount' => 25,
            'reason' => 'Approved support adjustment',
            'notes' => 'Manager reviewed at desk.',
        ])
        ->assertRedirect(route('finance.opd-payments.show', $visit))
        ->assertSessionHas('success', 'Discount requested successfully.');

    $discountId = DB::table('billing_discounts')
        ->where('patient_visit_id', $visit->id)
        ->value('id');

    expect($discountId)->not()->toBeNull();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('finance.opd-payments.discounts.approve', [$visit, $discountId]))
        ->assertRedirect(route('finance.opd-payments.show', $visit))
        ->assertSessionHas('success', 'Discount approved successfully.');

    $this->assertDatabaseHas('visit_billings', [
        'patient_visit_id' => $visit->id,
        'discount_amount' => '25.00',
        'balance_amount' => '125.00',
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('finance.opd-payments.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/opd-payments/show')
            ->where('visit.billing.discounts.0.status', 'approved')
            ->where('visit.billing.discount_amount', '25.00'));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('finance.opd-payments.discounts.reverse', [$visit, $discountId]), [
            'reversal_reason' => 'Discount approved on the wrong visit.',
        ])
        ->assertRedirect(route('finance.opd-payments.show', $visit))
        ->assertSessionHas('success', 'Discount reversed successfully.');

    $this->assertDatabaseHas('visit_billings', [
        'patient_visit_id' => $visit->id,
        'discount_amount' => '0.00',
        'balance_amount' => '150.00',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'tenant_id' => $visit->tenant_id,
        'branch_id' => $visit->facility_branch_id,
        'log_name' => 'billing',
        'event' => 'discount.reversed',
    ]);
});
