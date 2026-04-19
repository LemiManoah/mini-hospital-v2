<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_pos_carts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('cart_number', 50)->unique();
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->enum('status', ['active', 'held', 'converted', 'abandoned'])->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'user_id', 'status'], 'pharmacy_pos_carts_lookup');
        });

        Schema::create('pharmacy_pos_cart_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_pos_cart_id')->constrained('pharmacy_pos_carts')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pharmacy_pos_cart_id', 'inventory_item_id'], 'pharmacy_pos_cart_items_lookup');
        });

        Schema::create('pharmacy_pos_cart_item_allocations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('pharmacy_pos_cart_item_id');
            $table->foreign('pharmacy_pos_cart_item_id', 'pos_cart_alloc_item_fk')
                ->references('id')
                ->on('pharmacy_pos_cart_items')
                ->onDelete('cascade');
            $table->foreignUuid('inventory_batch_id')->constrained('inventory_batches')->onDelete('restrict');
            $table->decimal('quantity', 14, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_pos_cart_item_allocations');
        Schema::dropIfExists('pharmacy_pos_cart_items');
        Schema::dropIfExists('pharmacy_pos_carts');
    }
};
