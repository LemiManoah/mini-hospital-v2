<?php

declare(strict_types=1);

use App\Actions\RecalculateVisitBilling;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('recalculates visit billing totals from charges and payments', function (): void {
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
        'visit_number' => 'VIS-101',
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
        'gross_amount' => 0,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => 0,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_charges')->insert([
        [
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'facility_branch_id' => $branchId,
            'visit_billing_id' => $billingId,
            'patient_visit_id' => $visitId,
            'source_type' => 'manual',
            'source_id' => (string) Str::uuid(),
            'description' => 'Consultation fee',
            'quantity' => 1,
            'unit_price' => 50,
            'line_total' => 50,
            'status' => 'active',
            'charged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'facility_branch_id' => $branchId,
            'visit_billing_id' => $billingId,
            'patient_visit_id' => $visitId,
            'source_type' => 'manual',
            'source_id' => (string) Str::uuid(),
            'description' => 'Lab fee',
            'quantity' => 1,
            'unit_price' => 25,
            'line_total' => 25,
            'status' => 'active',
            'charged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visitId,
        'receipt_number' => 'RCT-1001',
        'payment_date' => now(),
        'amount' => 40,
        'payment_method' => 'cash',
        'is_refund' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $billing = resolve(RecalculateVisitBilling::class)->handle(
        VisitBilling::query()->findOrFail($billingId),
    );

    expect((float) $billing->gross_amount)->toBe(75.0)
        ->and((float) $billing->paid_amount)->toBe(40.0)
        ->and((float) $billing->balance_amount)->toBe(35.0)
        ->and($billing->status->value)->toBe('partial_paid')
        ->and($billing->billed_at)->not()->toBeNull()
        ->and($billing->settled_at)->toBeNull();

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visitId,
        'receipt_number' => 'RCT-1002',
        'payment_date' => now(),
        'amount' => 35,
        'payment_method' => 'cash',
        'is_refund' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $billing = resolve(RecalculateVisitBilling::class)->handle(
        VisitBilling::query()->findOrFail($billingId),
    );

    expect((float) $billing->balance_amount)->toBe(0.0)
        ->and((float) $billing->paid_amount)->toBe(75.0)
        ->and($billing->status->value)->toBe('fully_paid')
        ->and($billing->settled_at)->not()->toBeNull();
});
