<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('insurance_company_invoice_payments')) {
            Schema::create('insurance_company_invoice_payments', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
                $table->uuid('insurance_company_invoice_id');
                $table->foreign('insurance_company_invoice_id', 'ic_invoice_payments_invoice_fk')
                    ->references('id')
                    ->on('insurance_company_invoices')
                    ->onDelete('cascade');
                $table->date('payment_date');
                $table->string('receipt', 100)->nullable()->index();
                $table->decimal('paid_amount', 14, 2)->default(0);

                // Audit fields
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_company_invoice_payments');
    }
};
