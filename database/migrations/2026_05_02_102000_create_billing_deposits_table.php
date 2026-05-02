<?php

declare(strict_types=1);

use App\Enums\BillingDepositStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_deposits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignUuid('patient_visit_id')->nullable()->constrained('patient_visits')->nullOnDelete();
            $table->foreignUuid('visit_billing_id')->nullable()->constrained('visit_billings')->nullOnDelete();
            $table->string('deposit_number', 50)->unique();
            $table->foreignUuid('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('refunded_amount', 15, 2)->default(0);
            $table->enum('status', array_column(BillingDepositStatus::cases(), 'value'))
                ->default(BillingDepositStatus::Held->value)
                ->index();
            $table->timestamp('received_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'facility_branch_id', 'status'], 'billing_deposits_workspace_idx');
            $table->index(['patient_id', 'status'], 'billing_deposits_patient_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_deposits');
    }
};
