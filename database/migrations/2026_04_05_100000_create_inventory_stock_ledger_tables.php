<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignUuid('goods_receipt_item_id')->nullable()->constrained('goods_receipt_items')->nullOnDelete();
            $table->string('batch_number', 100)->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('quantity_received', 14, 3);
            $table->timestamp('received_at');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['goods_receipt_item_id']);
            $table->index(['tenant_id', 'branch_id', 'inventory_location_id', 'inventory_item_id'], 'inventory_batches_lookup');
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignUuid('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('movement_type', 50);
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->string('source_document_type', 150)->nullable();
            $table->uuid('source_document_id')->nullable();
            $table->string('source_line_type', 150)->nullable();
            $table->uuid('source_line_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'inventory_location_id', 'inventory_item_id'], 'stock_movements_lookup');
            $table->index(['source_document_type', 'source_document_id'], 'stock_movements_document_lookup');
            $table->index(['source_line_type', 'source_line_id'], 'stock_movements_line_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_batches');
    }
};
