<?php

declare(strict_types=1);

use App\Enums\BillingStatus;
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
        Schema::create('insurance_company_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->foreignUuid('insurance_company_id')->constrained('insurance_companies')->onDelete('cascade');
            $table->string('code', 30)->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('bill_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->enum('status', array_column(BillingStatus::cases(), 'value'))->default(BillingStatus::PENDING->value);
            $table->boolean('is_printed')->default(false);

            // Audit fields
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_company_invoices');
    }
};
