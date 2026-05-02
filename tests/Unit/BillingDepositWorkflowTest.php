<?php

declare(strict_types=1);

use App\Actions\ApplyBillingDeposit;
use App\Actions\RecordBillingDeposit;
use App\Enums\BillingDepositStatus;
use App\Models\Activity;
use App\Models\BillingDeposit;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

function seedBillingDepositVisit(float $chargeAmount = 100.0): array
{
    $tenantId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $paymentMethodId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);

    DB::table('payment_methods')->insert([
        'id' => $paymentMethodId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'code' => 'cash',
        'name' => 'Cash',
        'type' => 'cash',
        'requires_reference' => false,
        'is_active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-DEP-001',
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
        'billing_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'patient_visit_id' => $visitId,
        'visit_payer_id' => $payerId,
        'payer_type' => 'cash',
        'gross_amount' => $chargeAmount,
        'discount_amount' => 0,
        'write_off_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => $chargeAmount,
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
        'description' => 'Deposit-covered service',
        'quantity' => 1,
        'unit_price' => $chargeAmount,
        'line_total' => $chargeAmount,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    return [
        Patient::query()->findOrFail($patientId),
        VisitBilling::query()->findOrFail($billingId),
        $branchId,
        $paymentMethodId,
        $user,
    ];
}

it('records and applies a billing deposit through the normal payment recalculation path', function (): void {
    [$patient, $billing, $branchId, $paymentMethodId, $user] = seedBillingDepositVisit(chargeAmount: 100);

    actingAs($user);

    $deposit = resolve(RecordBillingDeposit::class)->handle(
        patient: $patient,
        branchId: $branchId,
        amount: 75,
        paymentMethodId: $paymentMethodId,
    );

    expect($deposit->status)->toBe(BillingDepositStatus::Held)
        ->and($deposit->deposit_number)->toStartWith('DEP-')
        ->and((float) $deposit->amount)->toBe(75.0)
        ->and(Activity::query()->where('event', 'deposit.recorded')->exists())->toBeTrue();

    $deposit = resolve(ApplyBillingDeposit::class)->handle($deposit, $billing, 40, 'Applied at cashier desk.');
    $billing->refresh();

    expect($deposit->status)->toBe(BillingDepositStatus::PartiallyApplied)
        ->and((float) $deposit->applied_amount)->toBe(40.0)
        ->and((float) $billing->paid_amount)->toBe(40.0)
        ->and((float) $billing->balance_amount)->toBe(60.0)
        ->and(DB::table('payments')->where('payment_method', 'deposit')->where('reference_number', $deposit->deposit_number)->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'deposit.applied')->exists())->toBeTrue();
});

it('prevents applying more than the held deposit balance', function (): void {
    [$patient, $billing, $branchId, $paymentMethodId, $user] = seedBillingDepositVisit(chargeAmount: 100);

    actingAs($user);

    $deposit = resolve(RecordBillingDeposit::class)->handle($patient, $branchId, 30, $paymentMethodId);

    resolve(ApplyBillingDeposit::class)->handle($deposit, $billing, 31);
})->throws(ValidationException::class);

it('allocates controlled deposit numbers sequentially per branch', function (): void {
    [$patient, , $branchId, $paymentMethodId, $user] = seedBillingDepositVisit(chargeAmount: 100);

    actingAs($user);

    $first = resolve(RecordBillingDeposit::class)->handle($patient, $branchId, 10, $paymentMethodId);
    $second = resolve(RecordBillingDeposit::class)->handle($patient, $branchId, 15, $paymentMethodId);

    expect($first->deposit_number)->toBe('DEP-2026-000001')
        ->and($second->deposit_number)->toBe('DEP-2026-000002')
        ->and(BillingDeposit::query()->count())->toBe(2);
});
