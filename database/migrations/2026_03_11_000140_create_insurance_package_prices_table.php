<?php

declare(strict_types=1);

use App\Enums\BillableItemType;
use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Enums\InsurancePolicyType;
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
        if (! Schema::hasTable('insurance_policies')) {
            Schema::create('insurance_policies', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
                $table->foreignUuid('insurance_package_id')->constrained('insurance_packages')->onDelete('cascade');
                $table->string('name', 150);
                $table->enum('policy_type', array_column(InsurancePolicyType::cases(), 'value'))->index();
                $table->date('effective_from')->nullable()->index();
                $table->date('effective_to')->nullable()->index();
                $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value)->index();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'facility_branch_id', 'insurance_package_id', 'name'],
                    'insurance_policies_unique_name'
                );
                $table->index(
                    ['tenant_id', 'facility_branch_id', 'insurance_package_id', 'policy_type', 'status'],
                    'insurance_policies_lookup_idx'
                );
            });
        }

        if (! Schema::hasTable('insurance_policy_items')) {
            Schema::create('insurance_policy_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('insurance_policy_id')->constrained('insurance_policies')->onDelete('cascade');
                $table->enum('item_type', array_column(BillableItemType::cases(), 'value'))->index();
                $table->uuid('item_id')->index();
                $table->decimal('price', 14, 2)->default(0);
                $table->enum('copay_type', array_column(InsuranceCopayType::cases(), 'value'))->default(InsuranceCopayType::NONE->value);
                $table->decimal('copay_value', 14, 2)->default(0);
                $table->date('effective_from')->nullable()->index();
                $table->date('effective_to')->nullable()->index();
                $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value)->index();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'insurance_policy_id', 'item_type', 'item_id', 'effective_from'],
                    'insurance_policy_items_unique_version'
                );
                $table->index(
                    ['tenant_id', 'insurance_policy_id', 'item_type', 'item_id', 'status'],
                    'insurance_policy_items_lookup_idx'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_policy_items');
        Schema::dropIfExists('insurance_policies');
    }
};
