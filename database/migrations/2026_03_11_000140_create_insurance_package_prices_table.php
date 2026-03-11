<?php

declare(strict_types=1);

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_package_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('insurance_package_id')->constrained('insurance_packages')->onDelete('cascade');
            $table->enum('billable_type', array_column(BillableItemType::cases(), 'value'))->index();
            $table->uuid('billable_id')->index();
            $table->decimal('price', 14, 2)->default(0);
            $table->date('effective_from')->nullable()->index();
            $table->date('effective_to')->nullable()->index();
            $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value)->index();

            // Audit fields
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'facility_branch_id', 'insurance_package_id', 'billable_type', 'billable_id', 'effective_from'],
                'ipp_unique_item_version'
            );
            $table->index(
                ['tenant_id', 'facility_branch_id', 'insurance_package_id', 'billable_type', 'billable_id', 'status'],
                'ipp_lookup_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_package_prices');
    }
};
