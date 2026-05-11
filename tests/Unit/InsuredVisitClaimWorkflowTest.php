<?php

declare(strict_types=1);

use App\Actions\RecalculateVisitBilling;
use App\Enums\InsuredVisitClaimStatus;
use App\Models\Activity;
use App\Models\InsuredVisitClaim;
use App\Models\User;
use App\Models\VisitBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function seedInsuranceClaimBilling(string $payerType = 'insurance'): array
{
    $tenantId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $patientId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $insuranceCompanyId = (string) Str::uuid();
    $insurancePackageId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);
    seedPatientRecord($patientId, $tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);
    seedInsuranceCoverage($tenantId, $insuranceCompanyId, $insurancePackageId);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => 'VIS-CLAIM-001',
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
        'billing_type' => $payerType,
        'insurance_company_id' => $payerType === 'insurance' ? $insuranceCompanyId : null,
        'insurance_package_id' => $payerType === 'insurance' ? $insurancePackageId : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'patient_visit_id' => $visitId,
        'visit_payer_id' => $payerId,
        'payer_type' => $payerType,
        'insurance_company_id' => $payerType === 'insurance' ? $insuranceCompanyId : null,
        'insurance_package_id' => $payerType === 'insurance' ? $insurancePackageId : null,
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
        'description' => 'Insured consultation fee',
        'quantity' => 1,
        'unit_price' => 100,
        'line_total' => 100,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    return [
        $tenantId,
        $branchId,
        $insuranceCompanyId,
        $insurancePackageId,
        $user,
        VisitBilling::query()->findOrFail($billingId),
    ];
}

it('creates a ready insured visit claim when insured billing is recalculated', function (): void {
    [, , $insuranceCompanyId, $insurancePackageId, $user, $billing] = seedInsuranceClaimBilling();

    actingAs($user);

    $billing = resolve(RecalculateVisitBilling::class)->handle($billing);
    $claim = InsuredVisitClaim::query()->where('visit_billing_id', $billing->id)->firstOrFail();

    expect($billing->status->value)->toBe('insurance_pending')
        ->and((float) $billing->gross_amount)->toBe(100.0)
        ->and($claim->status)->toBe(InsuredVisitClaimStatus::READY_FOR_INVOICE)
        ->and($claim->claim_reference)->toStartWith('CLM-')
        ->and((float) $claim->claimed_amount)->toBe(100.0)
        ->and((float) $claim->approved_amount)->toBe(0.0)
        ->and($claim->insurance_company_id)->toBe($insuranceCompanyId)
        ->and($claim->insurance_package_id)->toBe($insurancePackageId)
        ->and(Activity::query()->where('event', 'insurance_claim.created')->exists())->toBeTrue();
});

it('updates claim amount while the claim is still ready for invoice', function (): void {
    [, , , , $user, $billing] = seedInsuranceClaimBilling();

    actingAs($user);

    $billing = resolve(RecalculateVisitBilling::class)->handle($billing);
    $claim = InsuredVisitClaim::query()->where('visit_billing_id', $billing->id)->firstOrFail();

    DB::table('visit_charges')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $billing->tenant_id,
        'facility_branch_id' => $billing->facility_branch_id,
        'visit_billing_id' => $billing->id,
        'patient_visit_id' => $billing->patient_visit_id,
        'source_type' => 'manual',
        'source_id' => (string) Str::uuid(),
        'description' => 'Insured lab fee',
        'quantity' => 1,
        'unit_price' => 50,
        'line_total' => 50,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    resolve(RecalculateVisitBilling::class)->handle($billing);
    $claim->refresh();

    expect((float) $claim->claimed_amount)->toBe(150.0)
        ->and($claim->status)->toBe(InsuredVisitClaimStatus::READY_FOR_INVOICE)
        ->and(Activity::query()->where('event', 'insurance_claim.synced')->exists())->toBeTrue();
});

it('syncs active visit charge copays onto insured visit claims', function (): void {
    [, , , , $user, $billing] = seedInsuranceClaimBilling();

    actingAs($user);

    DB::table('visit_charges')
        ->where('visit_billing_id', $billing->id)
        ->update(['copay_amount' => 20]);

    resolve(RecalculateVisitBilling::class)->handle($billing);
    $claim = InsuredVisitClaim::query()->where('visit_billing_id', $billing->id)->firstOrFail();

    expect((float) $claim->claimed_amount)->toBe(100.0)
        ->and((float) $claim->copay_amount)->toBe(20.0);
});

it('does not rewrite an invoiced claim when visit billing later changes', function (): void {
    [, , , , $user, $billing] = seedInsuranceClaimBilling();

    actingAs($user);

    $billing = resolve(RecalculateVisitBilling::class)->handle($billing);
    $claim = InsuredVisitClaim::query()->where('visit_billing_id', $billing->id)->firstOrFail();

    $claim->forceFill([
        'status' => InsuredVisitClaimStatus::INVOICED,
        'claimed_amount' => 100,
        'invoiced_at' => now(),
    ])->save();

    DB::table('visit_charges')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $billing->tenant_id,
        'facility_branch_id' => $billing->facility_branch_id,
        'visit_billing_id' => $billing->id,
        'patient_visit_id' => $billing->patient_visit_id,
        'source_type' => 'manual',
        'source_id' => (string) Str::uuid(),
        'description' => 'Late insured service fee',
        'quantity' => 1,
        'unit_price' => 75,
        'line_total' => 75,
        'status' => 'active',
        'charged_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $billing = resolve(RecalculateVisitBilling::class)->handle($billing);
    $claim->refresh();

    expect((float) $billing->gross_amount)->toBe(175.0)
        ->and((float) $claim->claimed_amount)->toBe(100.0)
        ->and($claim->status)->toBe(InsuredVisitClaimStatus::INVOICED);
});

it('does not create an insured claim for cash billing', function (): void {
    [, , , , $user, $billing] = seedInsuranceClaimBilling('cash');

    actingAs($user);

    resolve(RecalculateVisitBilling::class)->handle($billing);

    expect(InsuredVisitClaim::query()->where('visit_billing_id', $billing->id)->exists())->toBeFalse();
});
