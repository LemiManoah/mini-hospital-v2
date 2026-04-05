<?php

declare(strict_types=1);

use App\Enums\StockCountStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('count_number', 50)->index();
            $table->enum('status', array_column(StockCountStatus::cases(), 'value'))
                ->default(StockCountStatus::Draft->value)
                ->index();
            $table->date('count_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'count_number']);
            $table->index(['tenant_id', 'branch_id', 'status'], 'stock_counts_lookup');
        });

        Schema::create('stock_count_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_count_id')->constrained('stock_counts')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->decimal('expected_quantity', 14, 3);
            $table->decimal('counted_quantity', 14, 3);
            $table->decimal('variance_quantity', 14, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_count_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
    }
};
