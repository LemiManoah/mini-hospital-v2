<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_pos_sales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('restrict');
            $table->foreignUuid('pharmacy_pos_cart_id')->nullable()->constrained('pharmacy_pos_carts')->nullOnDelete();
            $table->string('sale_number', 50)->unique();
            $table->string('sale_type', 50)->default('walk_in');
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->decimal('change_amount', 14, 2)->default(0);
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->enum('status', ['draft', 'completed', 'cancelled', 'refunded'])->default('draft')->index();
            $table->timestamp('sold_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'branch_id', 'status'], 'pharmacy_pos_sales_lookup');
        });

        Schema::create('pharmacy_pos_sale_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_pos_sale_id')->constrained('pharmacy_pos_sales')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_pos_sale_item_allocations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('pharmacy_pos_sale_item_id');
            $table->foreign('pharmacy_pos_sale_item_id', 'pos_sale_alloc_item_fk')
                ->references('id')
                ->on('pharmacy_pos_sale_items')
                ->onDelete('cascade');
            $table->foreignUuid('inventory_batch_id')->constrained('inventory_batches')->onDelete('restrict');
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('unit_cost_snapshot', 14, 2)->default(0);
            $table->string('batch_number_snapshot', 100)->nullable();
            $table->date('expiry_date_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_pos_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_pos_sale_id')->constrained('pharmacy_pos_sales')->onDelete('cascade');
            $table->string('receipt_number', 50)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->timestamp('payment_date');
            $table->boolean('is_refund')->default(false);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_pos_payments');
        Schema::dropIfExists('pharmacy_pos_sale_item_allocations');
        Schema::dropIfExists('pharmacy_pos_sale_items');
        Schema::dropIfExists('pharmacy_pos_sales');
    }
};
