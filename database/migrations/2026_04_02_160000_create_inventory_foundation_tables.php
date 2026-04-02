<?php

declare(strict_types=1);

use App\Enums\InventoryLocationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_locations')) {
            Schema::create('inventory_locations', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
                $table->string('name', 150)->index();
                $table->string('location_code', 50);
                $table->enum('type', array_column(InventoryLocationType::cases(), 'value'))->default(InventoryLocationType::OTHER->value)->index();
                $table->text('description')->nullable();
                $table->boolean('is_dispensing_point')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['branch_id', 'location_code']);
                $table->index(['tenant_id', 'branch_id', 'type']);
            });
        }

        if (! Schema::hasTable('inventory_location_items')) {
            Schema::create('inventory_location_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
                $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
                $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->decimal('minimum_stock_level', 14, 3)->default(0);
                $table->decimal('reorder_level', 14, 3)->default(0);
                $table->decimal('default_selling_price', 14, 2)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['inventory_location_id', 'inventory_item_id'], 'inventory_location_items_unique');
                $table->index(['tenant_id', 'branch_id', 'inventory_item_id'], 'inventory_location_items_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_location_items');
        Schema::dropIfExists('inventory_locations');
    }
};
