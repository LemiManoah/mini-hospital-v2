<?php

declare(strict_types=1);

use App\Enums\ReconciliationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reconciliations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('adjustment_number', 50)->index();
            $table->enum('status', array_column(ReconciliationStatus::cases(), 'value'))
                ->default(ReconciliationStatus::Draft->value)
                ->index();
            $table->date('adjustment_date');
            $table->string('reason', 200);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignUuid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'adjustment_number']);
            $table->index(['tenant_id', 'branch_id', 'status'], 'stock_reconciliations_lookup');
        });

        Schema::create('stock_reconciliation_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_reconciliation_id')->constrained('stock_reconciliations')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignUuid('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity_delta', 14, 3);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('expected_quantity', 14, 3)->nullable();
            $table->decimal('actual_quantity', 14, 3)->nullable();
            $table->decimal('variance_quantity', 14, 3)->nullable();

            $table->timestamps();

            $table->index(['stock_reconciliation_id', 'inventory_item_id'], 'stock_reconciliation_items_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reconciliation_items');
        Schema::dropIfExists('stock_reconciliations');
    }
};
