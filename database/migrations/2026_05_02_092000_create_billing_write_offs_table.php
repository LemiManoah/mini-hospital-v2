<?php

declare(strict_types=1);

use App\Enums\BillingWriteOffStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_write_offs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('visit_billing_id')->constrained('visit_billings')->onDelete('cascade');
            $table->foreignUuid('patient_visit_id')->constrained('patient_visits')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('reason', 255);
            $table->enum('status', array_column(BillingWriteOffStatus::cases(), 'value'))
                ->default(BillingWriteOffStatus::PENDING->value)
                ->index();
            $table->text('notes')->nullable();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 255)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'facility_branch_id', 'status'], 'billing_write_offs_workspace_idx');
            $table->index(['visit_billing_id', 'status'], 'billing_write_offs_billing_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_write_offs');
    }
};
