<?php

declare(strict_types=1);

use App\Actions\ApproveBillingDiscount;
use App\Actions\RecalculateVisitBilling;
use App\Actions\RequestBillingDiscount;
use App\Actions\ReverseBillingDiscount;
use App\Enums\BillingDiscountStatus;
use App\Models\Activity;
use App\Models\BillingDiscount;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

function seedBillingDiscountVisit(float $chargeAmount = 100.0, float $paidAmount = 0.0): array
{
    $tenantId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $branchId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-DISC-001',
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
        'paid_amount' => $paidAmount,
        'balance_amount' => max(0, $chargeAmount - $paidAmount),
        'status' => $paidAmount > 0 ? 'partial_paid' : 'pending',
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
        'unit_price' => $chargeAmount,
        'line_total' => $chargeAmount,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if ($paidAmount > 0) {
        DB::table('payments')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'facility_branch_id' => $branchId,
            'visit_billing_id' => $billingId,
            'patient_visit_id' => $visitId,
            'receipt_number' => 'RCT-DISC-001',
            'payment_date' => now(),
            'amount' => $paidAmount,
            'payment_method' => 'cash',
            'is_refund' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    return [$tenantId, $branchId, $user, VisitBilling::query()->findOrFail($billingId)];
}

it('requests and approves a billing discount while recalculating the visit balance', function (): void {
    [, , $user, $billing] = seedBillingDiscountVisit(chargeAmount: 100, paidAmount: 30);

    actingAs($user);

    $discount = resolve(RequestBillingDiscount::class)->handle(
        $billing,
        20,
        'Staff-approved goodwill adjustment',
        'Patient waited longer than expected.',
    );

    $billing->refresh();

    expect($discount->status)->toBe(BillingDiscountStatus::PENDING)
        ->and((float) $discount->amount)->toBe(20.0)
        ->and((float) $billing->discount_amount)->toBe(0.0)
        ->and((float) $billing->balance_amount)->toBe(70.0);

    $discount = resolve(ApproveBillingDiscount::class)->handle($discount);
    $billing->refresh();

    expect($discount->status)->toBe(BillingDiscountStatus::APPROVED)
        ->and($discount->approved_by)->toBe($user->id)
        ->and($discount->approved_at)->not()->toBeNull()
        ->and((float) $billing->gross_amount)->toBe(100.0)
        ->and((float) $billing->paid_amount)->toBe(30.0)
        ->and((float) $billing->discount_amount)->toBe(20.0)
        ->and((float) $billing->balance_amount)->toBe(50.0)
        ->and($billing->status->value)->toBe('partial_paid');

    expect(Activity::query()->where('event', 'discount.requested')->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'discount.approved')->exists())->toBeTrue();
});

it('reverses an approved billing discount and restores the outstanding balance', function (): void {
    [, , $user, $billing] = seedBillingDiscountVisit(chargeAmount: 80, paidAmount: 0);

    actingAs($user);

    $discount = resolve(RequestBillingDiscount::class)->handle($billing, 25, 'Charity support');
    $discount = resolve(ApproveBillingDiscount::class)->handle($discount);

    expect((float) $billing->refresh()->discount_amount)->toBe(25.0)
        ->and((float) $billing->balance_amount)->toBe(55.0);

    $discount = resolve(ReverseBillingDiscount::class)->handle($discount, 'Discount approved in error.');
    $billing->refresh();

    expect($discount->status)->toBe(BillingDiscountStatus::REVERSED)
        ->and($discount->reversed_by)->toBe($user->id)
        ->and($discount->reversal_reason)->toBe('Discount approved in error.')
        ->and((float) $billing->discount_amount)->toBe(0.0)
        ->and((float) $billing->balance_amount)->toBe(80.0)
        ->and(Activity::query()->where('event', 'discount.reversed')->exists())->toBeTrue();
});

it('rejects a discount greater than the outstanding balance', function (): void {
    [, , $user, $billing] = seedBillingDiscountVisit(chargeAmount: 100, paidAmount: 75);

    actingAs($user);

    resolve(RequestBillingDiscount::class)->handle($billing, 30, 'Too much');
})->throws(ValidationException::class);

it('does not approve a pending discount if payments already cleared the balance', function (): void {
    [, , $user, $billing] = seedBillingDiscountVisit(chargeAmount: 100, paidAmount: 0);

    actingAs($user);

    $discount = resolve(RequestBillingDiscount::class)->handle($billing, 30, 'Needs review');

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $billing->tenant_id,
        'facility_branch_id' => $billing->facility_branch_id,
        'visit_billing_id' => $billing->id,
        'patient_visit_id' => $billing->patient_visit_id,
        'receipt_number' => 'RCT-DISC-PAID',
        'payment_date' => now(),
        'amount' => 100,
        'payment_method' => 'cash',
        'is_refund' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    resolve(ApproveBillingDiscount::class)->handle($discount);
})->throws(ValidationException::class);

it('recalculates discount totals from approved discount records only', function (): void {
    [, , $user, $billing] = seedBillingDiscountVisit(chargeAmount: 120, paidAmount: 0);

    actingAs($user);

    $approved = resolve(RequestBillingDiscount::class)->handle($billing, 15, 'Approved support');
    resolve(ApproveBillingDiscount::class)->handle($approved);

    BillingDiscount::query()->create([
        'tenant_id' => $billing->tenant_id,
        'facility_branch_id' => $billing->facility_branch_id,
        'visit_billing_id' => $billing->id,
        'patient_visit_id' => $billing->patient_visit_id,
        'amount' => 40,
        'reason' => 'Pending support',
        'status' => BillingDiscountStatus::PENDING,
        'requested_at' => now(),
    ]);

    $billing = resolve(RecalculateVisitBilling::class)->handle($billing);

    expect((float) $billing->discount_amount)->toBe(15.0)
        ->and((float) $billing->balance_amount)->toBe(105.0);
});
