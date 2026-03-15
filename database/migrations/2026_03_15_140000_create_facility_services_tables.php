<?php

declare(strict_types=1);

use App\Enums\FacilityServiceCategory;
use App\Enums\FacilityServiceOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('service_code', 50);
            $table->string('name', 150)->index();
            $table->enum('category', array_column(FacilityServiceCategory::cases(), 'value'))->default(FacilityServiceCategory::OTHER->value);
            $table->string('department_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('default_instructions')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->uuid('charge_master_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'service_code']);
        });

        Schema::create('facility_service_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
            $table->foreignUuid('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignUuid('facility_service_id')->constrained('facility_services')->onDelete('restrict');
            $table->foreignUuid('ordered_by')->constrained('staff')->onDelete('restrict');
            $table->enum('status', array_column(FacilityServiceOrderStatus::cases(), 'value'))->default(FacilityServiceOrderStatus::PENDING->value)->index();
            $table->text('clinical_notes')->nullable();
            $table->text('service_instructions')->nullable();
            $table->timestamp('ordered_at')->useCurrent();
            $table->foreignUuid('performed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['visit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_service_orders');
        Schema::dropIfExists('facility_services');
    }
};
