<?php

declare(strict_types=1);

use App\Enums\BillableItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_masters', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->string('item_code', 50);
            $table->string('description', 255);
            $table->enum('billable_type', array_column(BillableItemType::cases(), 'value'))->nullable()->index();
            $table->uuid('billable_id')->nullable()->index();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->date('effective_from')->nullable()->index();
            $table->date('effective_to')->nullable()->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'facility_branch_id', 'item_code', 'effective_from'], 'charge_masters_item_code_effective_unique');
            $table->index(['tenant_id', 'facility_branch_id', 'billable_type', 'billable_id'], 'charge_masters_billable_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_masters');
    }
};
