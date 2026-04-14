<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensing_record_item_allocations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispensing_record_item_id')->constrained('dispensing_record_items')->onDelete('cascade');
            $table->foreignUuid('inventory_batch_id')->constrained('inventory_batches')->onDelete('restrict');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost_snapshot', 14, 2)->nullable();
            $table->string('batch_number_snapshot', 100)->nullable();
            $table->date('expiry_date_snapshot')->nullable();
            $table->timestamps();

            $table->index(
                ['dispensing_record_item_id', 'inventory_batch_id'],
                'dispense_item_allocations_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensing_record_item_allocations');
    }
};
