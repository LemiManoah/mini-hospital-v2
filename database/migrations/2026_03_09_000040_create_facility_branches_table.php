<?php

declare(strict_types=1);

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
        if (! Schema::hasTable('facility_branches')) {
            Schema::create('facility_branches', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name')->index();
                $table->string('branch_code', 20);
                $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
                $table->string('main_contact')->nullable()->index();
                $table->string('other_contact')->nullable();
                $table->string('email')->nullable()->index();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('currency_id')->constrained('currencies')->onDelete('cascade');
                $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value);
                $table->boolean('is_main_branch')->default(false);
                $table->boolean('has_store')->default(false);

                // Audit fields
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'branch_code']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_branches');
    }
};
