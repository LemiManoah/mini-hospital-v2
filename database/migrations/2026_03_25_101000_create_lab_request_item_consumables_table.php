<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_request_item_consumables')) {
            Schema::create('lab_request_item_consumables', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
                $table->foreignUuid('lab_request_item_id')->constrained('lab_request_items')->onDelete('cascade');
                $table->string('consumable_name', 150);
                $table->string('unit_label', 50)->nullable();
                $table->decimal('quantity', 10, 2);
                $table->decimal('unit_cost', 10, 2);
                $table->decimal('line_cost', 10, 2);
                $table->text('notes')->nullable();
                $table->timestamp('used_at')->useCurrent();
                $table->foreignUuid('recorded_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamps();

                $table->index(['lab_request_item_id', 'used_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_request_item_consumables');
    }
};
