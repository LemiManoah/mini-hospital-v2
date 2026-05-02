<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use App\Enums\InsuredVisitClaimStatus;
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

/**
 * @return array{tenant: Tenant, branch: FacilityBranch, user: User, insurance_company_id: string, insurance_package_id: string}
 */
function createInsuranceInvoiceControllerContext(): array
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

    $insuranceCompanyId = (string) Str::uuid();
    $insurancePackageId = (string) Str::uuid();

    DB::table('insurance_companies')->insert([
        'id' => $insuranceCompanyId,
        'tenant_id' => $tenant->id,
        'name' => 'Acme Health Assurance',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_packages')->insert([
        'id' => $insurancePackageId,
        'tenant_id' => $tenant->id,
        'insurance_company_id' => $insuranceCompanyId,
        'name' => 'Corporate Cover',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'tenant' => $tenant,
        'branch' => $branch,
        'user' => $user,
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
    ];
}

function createReadyInsuranceClaimForController(
    Tenant $tenant,
    FacilityBranch $branch,
    string $insuranceCompanyId,
    string $insurancePackageId,
    float $claimedAmount,
): string {
    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => sprintf('PAT-INS-%s', Str::upper(Str::random(6))),
        'first_name' => 'Insured',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700222222',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => sprintf('VIS-INS-%s', Str::upper(Str::random(6))),
        'visit_type' => 'opd_consultation',
        'status' => 'in_progress',
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();
    $claimId = (string) Str::uuid();

    DB::table('visit_payers')->insert([
        'id' => $payerId,
        'tenant_id' => $tenant->id,
        'patient_visit_id' => $visit->id,
        'billing_type' => 'insurance',
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'patient_visit_id' => $visit->id,
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
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_billing_id' => $billingId,
        'patient_visit_id' => $visit->id,
        'insurance_company_id' => $insuranceCompanyId,
        'insurance_package_id' => $insurancePackageId,
        'claim_reference' => sprintf('CLM-%s', Str::upper(Str::random(10))),
        'claimed_amount' => $claimedAmount,
        'approved_amount' => 0,
        'rejected_amount' => 0,
        'copay_amount' => 0,
        'paid_amount' => 0,
        'status' => InsuredVisitClaimStatus::READY_FOR_INVOICE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $claimId;
}

it('shows ready insurance claim batches in the finance insurance invoice queue', function (): void {
    $context = createInsuranceInvoiceControllerContext();
    $context['user']->givePermissionTo('insurance_claims.view');

    createReadyInsuranceClaimForController(
        $context['tenant'],
        $context['branch'],
        $context['insurance_company_id'],
        $context['insurance_package_id'],
        125,
    );

    $this->withSession(['active_branch_id' => $context['branch']->id])
        ->actingAs($context['user'])
        ->get(route('finance.insurance-invoices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/insurance-invoices/index')
            ->where('readyClaimBatches.0.insurance_company_name', 'Acme Health Assurance')
            ->where('readyClaimBatches.0.claims_count', 1)
            ->where('readyClaimBatches.0.claim_total', 125));
});

it('generates an insurance company invoice from the finance screen', function (): void {
    $context = createInsuranceInvoiceControllerContext();
    $context['user']->givePermissionTo(['insurance_claims.view', 'insurance_claims.create']);

    createReadyInsuranceClaimForController(
        $context['tenant'],
        $context['branch'],
        $context['insurance_company_id'],
        $context['insurance_package_id'],
        150,
    );

    $this->withSession(['active_branch_id' => $context['branch']->id])
        ->actingAs($context['user'])
        ->post(route('finance.insurance-invoices.store'), [
            'insurance_company_id' => $context['insurance_company_id'],
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Insurer invoice generated successfully.');

    $invoiceId = DB::table('insurance_company_invoices')
        ->where('insurance_company_id', $context['insurance_company_id'])
        ->value('id');

    expect($invoiceId)->not()->toBeNull();

    $this->assertDatabaseHas('insurance_company_invoices', [
        'id' => $invoiceId,
        'bill_amount' => '150.00',
        'paid_amount' => '0.00',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'tenant_id' => $context['tenant']->id,
        'branch_id' => $context['branch']->id,
        'log_name' => 'billing',
        'event' => 'insurance_invoice.generated',
    ]);
});

it('shows an insurance invoice and records allocated remittance payments', function (): void {
    $context = createInsuranceInvoiceControllerContext();
    $context['user']->givePermissionTo([
        'insurance_claims.view',
        'insurance_claims.create',
        'insurance_payments.create',
    ]);

    $firstClaimId = createReadyInsuranceClaimForController(
        $context['tenant'],
        $context['branch'],
        $context['insurance_company_id'],
        $context['insurance_package_id'],
        100,
    );
    $secondClaimId = createReadyInsuranceClaimForController(
        $context['tenant'],
        $context['branch'],
        $context['insurance_company_id'],
        $context['insurance_package_id'],
        50,
    );

    $this->withSession(['active_branch_id' => $context['branch']->id])
        ->actingAs($context['user'])
        ->post(route('finance.insurance-invoices.store'), [
            'insurance_company_id' => $context['insurance_company_id'],
        ]);

    $invoiceId = (string) DB::table('insurance_company_invoices')
        ->where('insurance_company_id', $context['insurance_company_id'])
        ->value('id');

    $this->withSession(['active_branch_id' => $context['branch']->id])
        ->actingAs($context['user'])
        ->get(route('finance.insurance-invoices.show', $invoiceId))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finance/insurance-invoices/show')
            ->where('invoice.bill_amount', 150)
            ->has('invoice.claims', 2)
            ->has('audit_activity', 1));

    $this->withSession(['active_branch_id' => $context['branch']->id])
        ->actingAs($context['user'])
        ->post(route('finance.insurance-invoices.payments.store', $invoiceId), [
            'paid_amount' => 125,
            'payment_date' => '2026-05-02',
            'receipt' => 'REM-FIN-001',
            'allocations' => [
                [
                    'insured_visit_claim_id' => $firstClaimId,
                    'allocated_amount' => 100,
                ],
                [
                    'insured_visit_claim_id' => $secondClaimId,
                    'allocated_amount' => 25,
                ],
            ],
        ])
        ->assertRedirect(route('finance.insurance-invoices.show', $invoiceId))
        ->assertSessionHas('success', 'Insurer payment recorded successfully.');

    $this->assertDatabaseHas('insurance_company_invoices', [
        'id' => $invoiceId,
        'paid_amount' => '125.00',
        'status' => 'partial_paid',
    ]);

    $this->assertDatabaseHas('insured_visit_claims', [
        'id' => $firstClaimId,
        'paid_amount' => '100.00',
        'status' => 'paid',
    ]);

    $this->assertDatabaseHas('insured_visit_claims', [
        'id' => $secondClaimId,
        'paid_amount' => '25.00',
        'status' => 'partially_paid',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'tenant_id' => $context['tenant']->id,
        'branch_id' => $context['branch']->id,
        'log_name' => 'billing',
        'event' => 'insurance_invoice_payment.recorded',
    ]);
});
