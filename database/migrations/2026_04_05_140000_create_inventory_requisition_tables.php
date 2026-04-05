<?php

declare(strict_types=1);

use App\Enums\InventoryRequisitionStatus;
use App\Enums\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_requisitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('source_inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->foreignUuid('destination_inventory_location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('requisition_number', 50)->index();
            $table->enum('status', array_column(InventoryRequisitionStatus::cases(), 'value'))
                ->default(InventoryRequisitionStatus::Draft->value)
                ->index();
            $table->enum('priority', array_column(Priority::cases(), 'value'))
                ->default(Priority::ROUTINE->value)
                ->index();
            $table->date('requisition_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignUuid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->text('issued_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'requisition_number']);
            $table->index(['tenant_id', 'branch_id', 'status'], 'inventory_requisitions_lookup');
        });

        Schema::create('inventory_requisition_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventory_requisition_id')->constrained('inventory_requisitions')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->decimal('requested_quantity', 14, 3);
            $table->decimal('approved_quantity', 14, 3)->default(0);
            $table->decimal('issued_quantity', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_requisition_id', 'inventory_item_id'], 'inventory_requisition_items_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_requisition_items');
        Schema::dropIfExists('inventory_requisitions');
    }
};
