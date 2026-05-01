<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claim_allocations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->foreignUuid('insured_visit_claim_id')->constrained('insured_visit_claims')->onDelete('cascade');
            $table->foreignUuid('insurance_company_invoice_id')->constrained('insurance_company_invoices')->onDelete('cascade');
            $table->uuid('insurance_company_invoice_payment_id')->nullable();
            $table->foreign('insurance_company_invoice_payment_id', 'insurance_claim_allocations_payment_fk')
                ->references('id')
                ->on('insurance_company_invoice_payments')
                ->nullOnDelete();
            $table->date('allocation_date');
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'facility_branch_id', 'allocation_date'], 'insurance_claim_allocations_workspace_idx');
            $table->index(['insurance_company_invoice_id', 'insurance_company_invoice_payment_id'], 'insurance_claim_allocations_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claim_allocations');
    }
};
