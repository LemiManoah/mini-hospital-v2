<?php

declare(strict_types=1);

use App\Enums\InsuredVisitClaimStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insured_visit_claims', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('visit_billing_id')->constrained('visit_billings')->onDelete('cascade');
            $table->foreignUuid('patient_visit_id')->constrained('patient_visits')->onDelete('cascade');
            $table->foreignUuid('insurance_company_id')->constrained('insurance_companies')->onDelete('cascade');
            $table->foreignUuid('insurance_package_id')->nullable()->constrained('insurance_packages')->nullOnDelete();
            $table->foreignUuid('insurance_company_invoice_id')->nullable()->constrained('insurance_company_invoices')->nullOnDelete();
            $table->string('claim_reference', 50);
            $table->decimal('claimed_amount', 15, 2)->default(0);
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('rejected_amount', 15, 2)->default(0);
            $table->decimal('copay_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', array_column(InsuredVisitClaimStatus::cases(), 'value'))
                ->default(InsuredVisitClaimStatus::OPEN->value)
                ->index();
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'claim_reference'], 'insured_visit_claims_reference_unique');
            $table->unique(['visit_billing_id'], 'insured_visit_claims_billing_unique');
            $table->unique(['patient_visit_id'], 'insured_visit_claims_visit_unique');
            $table->index(['tenant_id', 'facility_branch_id', 'status'], 'insured_visit_claims_workspace_idx');
            $table->index(['insurance_company_id', 'insurance_package_id', 'status'], 'insured_visit_claims_payer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insured_visit_claims');
    }
};
