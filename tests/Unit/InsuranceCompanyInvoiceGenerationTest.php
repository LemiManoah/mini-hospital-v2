<?php

declare(strict_types=1);

use App\Actions\GenerateInsuranceCompanyInvoice;
use App\Actions\RecordInsuranceCompanyInvoicePayment;
use App\Enums\BillingStatus;
use App\Enums\InsuredVisitClaimStatus;
use App\Models\Activity;
use App\Models\InsuranceClaimAllocation;
use App\Models\InsuranceCompanyInvoice;
use App\Models\InsuranceCompanyInvoicePayment;
use App\Models\InsuredVisitClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/**
 * @return array{tenant_id: string, branch_id: string, insurance_company_id: string, insurance_package_id: string, user: User}
 */
function seedInsuranceInvoiceWorkspace(): array
{
    $tenantId = (string) Str::uuid();
    $branchId = (string) Str::uuid();
    $insuranceCompanyId = (string) Str::uuid();
    $insurancePackageId = (string) Str::uuid();

    $tenantContext = seedTenantContext($tenantId);
    seedFacilityBranchRecord($branchId, $tenantId, $tenantContext['currency_id']);
    seedInsuranceCoverage($tenantId, $insuranceCompanyId, $insurancePackageId);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    return [
        'tenant_id' => $tenantId,
        'branch_id' => $branchId,
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'user' => $user,
    ];
}

function seedReadyInsuranceInvoiceClaim(
    string $tenantId,
    string $branchId,
    string $insuranceCompanyId,
    string $insurancePackageId,
    float $claimedAmount,
    InsuredVisitClaimStatus $status = InsuredVisitClaimStatus::READY_FOR_INVOICE,
): InsuredVisitClaim {
    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $claimId = (string) Str::uuid();

    seedPatientRecord($patientId, $tenantId);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenantId,
        'patient_id' => $patientId,
        'facility_branch_id' => $branchId,
        'visit_number' => sprintf('VIS-INV-%s', Str::upper(Str::random(8))),
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
        'billing_type' => 'insurance',
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'patient_visit_id' => $visitId,
        'visit_payer_id' => $payerId,
        'payer_type' => 'insurance',
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'gross_amount' => $claimedAmount,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => $claimedAmount,
        'status' => 'insurance_pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insured_visit_claims')->insert([
        'id' => $claimId,
        'tenant_id' => $tenantId,
        'facility_branch_id' => $branchId,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visitId,
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'claim_reference' => sprintf('CLM-%s', Str::upper(Str::random(10))),
        'claimed_amount' => $claimedAmount,
        'approved_amount' => 0,
        'rejected_amount' => 0,
        'copay_amount' => 0,
        'paid_amount' => 0,
        'status' => $status->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return InsuredVisitClaim::query()->findOrFail($claimId);
}

it('generates an insurer invoice from ready claims', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $firstClaim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        120,
    );
    $secondClaim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        80,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    expect($invoice->code)->toStartWith('ICI-')
        ->and($invoice->status)->toBe(BillingStatus::PENDING)
        ->and((float) $invoice->bill_amount)->toBe(200.0)
        ->and((float) $invoice->paid_amount)->toBe(0.0)
        ->and($invoice->claims()->count())->toBe(2)
        ->and(Activity::query()->where('event', 'insurance_invoice.generated')->exists())->toBeTrue();

    expect($firstClaim->refresh()->insurance_company_invoice_id)->toBe($invoice->id)
        ->and($firstClaim->status)->toBe(InsuredVisitClaimStatus::INVOICED)
        ->and($firstClaim->invoiced_at)->not->toBeNull()
        ->and($secondClaim->refresh()->insurance_company_invoice_id)->toBe($invoice->id)
        ->and($secondClaim->status)->toBe(InsuredVisitClaimStatus::INVOICED);
});

it('only batches ready claims for the requested insurer and branch', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $otherInsuranceCompanyId = (string) Str::uuid();
    $otherInsurancePackageId = (string) Str::uuid();
    seedInsuranceCoverage($workspace['tenant_id'], $otherInsuranceCompanyId, $otherInsurancePackageId);

    seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        90,
    );
    $otherInsurerClaim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $otherInsuranceCompanyId,
        $otherInsurancePackageId,
        60,
    );
    $submittedClaim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        40,
        InsuredVisitClaimStatus::SUBMITTED,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    expect((float) $invoice->bill_amount)->toBe(90.0)
        ->and($invoice->claims()->count())->toBe(1)
        ->and($otherInsurerClaim->refresh()->insurance_company_invoice_id)->toBeNull()
        ->and($otherInsurerClaim->status)->toBe(InsuredVisitClaimStatus::READY_FOR_INVOICE)
        ->and($submittedClaim->refresh()->insurance_company_invoice_id)->toBeNull()
        ->and($submittedClaim->status)->toBe(InsuredVisitClaimStatus::SUBMITTED);
});

it('rejects an empty invoice batch', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();

    actingAs($workspace['user']);

    resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );
})->throws(ValidationException::class);

it('exposes claim allocation relationships for insurer remittance work', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $claim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        125,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    DB::table('insurance_claim_allocations')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $workspace['tenant_id'],
        'facility_branch_id' => $workspace['branch_id'],
        'insured_visit_claim_id' => $claim->id,
        'insurance_company_invoice_id' => $invoice->id,
        'allocation_date' => now()->toDateString(),
        'allocated_amount' => 75,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($claim->refresh()->allocations()->count())->toBe(1)
        ->and(InsuranceCompanyInvoice::query()->findOrFail($invoice->id)->allocations()->count())->toBe(1);
});

it('records and allocates an insurer invoice payment to claims', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $firstClaim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        120,
    );
    $secondClaim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        80,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    $payment = resolve(RecordInsuranceCompanyInvoicePayment::class)->handle(
        invoice: $invoice,
        paidAmount: 150,
        allocations: [
            ['insured_visit_claim_id' => $firstClaim->id, 'allocated_amount' => 120],
            ['insured_visit_claim_id' => $secondClaim->id, 'allocated_amount' => 30],
        ],
        paymentDate: now()->toDateString(),
        receipt: 'REM-001',
    );

    expect($payment)->toBeInstanceOf(InsuranceCompanyInvoicePayment::class)
        ->and((float) $payment->paid_amount)->toBe(150.0)
        ->and($payment->receipt)->toBe('REM-001')
        ->and(InsuranceClaimAllocation::query()->where('insurance_company_invoice_payment_id', $payment->id)->count())->toBe(2)
        ->and(Activity::query()->where('event', 'insurance_invoice_payment.recorded')->exists())->toBeTrue();

    expect($invoice->refresh()->status)->toBe(BillingStatus::PARTIAL_PAID)
        ->and((float) $invoice->paid_amount)->toBe(150.0)
        ->and($firstClaim->refresh()->status)->toBe(InsuredVisitClaimStatus::PAID)
        ->and((float) $firstClaim->paid_amount)->toBe(120.0)
        ->and($firstClaim->paid_at)->not->toBeNull()
        ->and($secondClaim->refresh()->status)->toBe(InsuredVisitClaimStatus::PARTIALLY_PAID)
        ->and((float) $secondClaim->paid_amount)->toBe(30.0)
        ->and($secondClaim->paid_at)->toBeNull();
});

it('fully settles an insurer invoice after allocated payments reach the bill amount', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $claim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        75,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    resolve(RecordInsuranceCompanyInvoicePayment::class)->handle(
        invoice: $invoice,
        paidAmount: 75,
        allocations: [
            ['insured_visit_claim_id' => $claim->id, 'allocated_amount' => 75],
        ],
    );

    expect($invoice->refresh()->status)->toBe(BillingStatus::FULLY_PAID)
        ->and((float) $invoice->paid_amount)->toBe(75.0)
        ->and($claim->refresh()->status)->toBe(InsuredVisitClaimStatus::PAID)
        ->and((float) $claim->paid_amount)->toBe(75.0);
});

it('rejects insurer payment allocations that do not match the payment amount', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $claim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        75,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    resolve(RecordInsuranceCompanyInvoicePayment::class)->handle(
        invoice: $invoice,
        paidAmount: 75,
        allocations: [
            ['insured_visit_claim_id' => $claim->id, 'allocated_amount' => 50],
        ],
    );
})->throws(ValidationException::class);

it('rejects insurer payment allocations that exceed a claim balance', function (): void {
    $workspace = seedInsuranceInvoiceWorkspace();
    $claim = seedReadyInsuranceInvoiceClaim(
        $workspace['tenant_id'],
        $workspace['branch_id'],
        $workspace['insurance_company_id'],
        $workspace['insurance_package_id'],
        75,
    );

    actingAs($workspace['user']);

    $invoice = resolve(GenerateInsuranceCompanyInvoice::class)->handle(
        tenantId: $workspace['tenant_id'],
        branchId: $workspace['branch_id'],
        insuranceCompanyId: $workspace['insurance_company_id'],
    );

    resolve(RecordInsuranceCompanyInvoicePayment::class)->handle(
        invoice: $invoice,
        paidAmount: 80,
        allocations: [
            ['insured_visit_claim_id' => $claim->id, 'allocated_amount' => 80],
        ],
    );
})->throws(ValidationException::class);
