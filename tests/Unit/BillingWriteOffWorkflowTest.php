<?php

declare(strict_types=1);

use App\Actions\ApproveBillingWriteOff;
use App\Actions\RecalculateVisitBilling;
use App\Actions\RequestBillingWriteOff;
use App\Actions\ReverseBillingWriteOff;
use App\Enums\BillingWriteOffStatus;
use App\Models\Activity;
use App\Models\BillingWriteOff;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

function seedBillingWriteOffVisit(float $chargeAmount = 100.0, float $paidAmount = 0.0): array
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
        'visit_number' => 'VIS-WOFF-001',
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
        'description' => 'Outstanding debtor fee',
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
            'receipt_number' => 'RCT-WOFF-001',
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

it('requests and approves a billing write-off while recalculating debtor balance', function (): void {
    [, , $user, $billing] = seedBillingWriteOffVisit(chargeAmount: 100, paidAmount: 25);

    actingAs($user);

    $writeOff = resolve(RequestBillingWriteOff::class)->handle(
        $billing,
        75,
        'Bad debt approved by finance manager',
        'Collections exhausted.',
    );

    expect($writeOff->status)->toBe(BillingWriteOffStatus::PENDING)
        ->and((float) $billing->refresh()->write_off_amount)->toBe(0.0)
        ->and((float) $billing->balance_amount)->toBe(75.0);

    $writeOff = resolve(ApproveBillingWriteOff::class)->handle($writeOff);
    $billing->refresh();

    expect($writeOff->status)->toBe(BillingWriteOffStatus::APPROVED)
        ->and($writeOff->approved_by)->toBe($user->id)
        ->and($writeOff->approved_at)->not()->toBeNull()
        ->and((float) $billing->write_off_amount)->toBe(75.0)
        ->and((float) $billing->balance_amount)->toBe(0.0)
        ->and($billing->status->value)->toBe('written_off')
        ->and(Activity::query()->where('event', 'write_off.requested')->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'write_off.approved')->exists())->toBeTrue();
});

it('reverses an approved write-off and restores the debtor balance', function (): void {
    [, , $user, $billing] = seedBillingWriteOffVisit(chargeAmount: 80, paidAmount: 0);

    actingAs($user);

    $writeOff = resolve(RequestBillingWriteOff::class)->handle($billing, 30, 'Charity debt write-off');
    $writeOff = resolve(ApproveBillingWriteOff::class)->handle($writeOff);

    expect((float) $billing->refresh()->write_off_amount)->toBe(30.0)
        ->and((float) $billing->balance_amount)->toBe(50.0);

    $writeOff = resolve(ReverseBillingWriteOff::class)->handle($writeOff, 'Write-off approved for wrong balance.');
    $billing->refresh();

    expect($writeOff->status)->toBe(BillingWriteOffStatus::REVERSED)
        ->and($writeOff->reversed_by)->toBe($user->id)
        ->and($writeOff->reversal_reason)->toBe('Write-off approved for wrong balance.')
        ->and((float) $billing->write_off_amount)->toBe(0.0)
        ->and((float) $billing->balance_amount)->toBe(80.0)
        ->and(Activity::query()->where('event', 'write_off.reversed')->exists())->toBeTrue();
});

it('rejects a write-off greater than the outstanding balance', function (): void {
    [, , $user, $billing] = seedBillingWriteOffVisit(chargeAmount: 100, paidAmount: 75);

    actingAs($user);

    resolve(RequestBillingWriteOff::class)->handle($billing, 30, 'Too much');
})->throws(ValidationException::class);

it('recalculates write-off totals from approved records only', function (): void {
    [, , $user, $billing] = seedBillingWriteOffVisit(chargeAmount: 120, paidAmount: 0);

    actingAs($user);

    $approved = resolve(RequestBillingWriteOff::class)->handle($billing, 15, 'Approved bad debt');
    resolve(ApproveBillingWriteOff::class)->handle($approved);

    BillingWriteOff::query()->create([
        'tenant_id' => $billing->tenant_id,
        'facility_branch_id' => $billing->facility_branch_id,
        'visit_billing_id' => $billing->id,
        'patient_visit_id' => $billing->patient_visit_id,
        'amount' => 40,
        'reason' => 'Pending bad debt',
        'status' => BillingWriteOffStatus::PENDING,
        'requested_at' => now(),
    ]);

    $billing = resolve(RecalculateVisitBilling::class)->handle($billing);

    expect((float) $billing->write_off_amount)->toBe(15.0)
        ->and((float) $billing->balance_amount)->toBe(105.0);
});
