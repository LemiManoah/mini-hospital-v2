<?php

declare(strict_types=1);

use App\Enums\LabBillingStatus;
use App\Enums\LabRequestItemStatus;
use App\Enums\LabRequestStatus;
use App\Enums\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lab_requests')) {
            Schema::create('lab_requests', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
                $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
                $table->foreignUuid('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
                $table->foreignUuid('requested_by')->constrained('staff')->onDelete('restrict');
                $table->timestamp('request_date')->useCurrent();
                $table->text('clinical_notes')->nullable();
                $table->enum('priority', array_column(Priority::cases(), 'value'))->default(Priority::ROUTINE->value);
                $table->enum('status', array_column(LabRequestStatus::cases(), 'value'))->default(LabRequestStatus::REQUESTED->value)->index();
                $table->string('diagnosis_code', 10)->nullable();
                $table->boolean('is_stat')->default(false);
                $table->enum('billing_status', array_column(LabBillingStatus::cases(), 'value'))->default(LabBillingStatus::PENDING->value);
                $table->text('cancellation_reason')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'priority']);
                $table->index('request_date');
            });
        }

        if (!Schema::hasTable('lab_request_items')) {
            Schema::create('lab_request_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('request_id')->constrained('lab_requests')->onDelete('cascade');
                $table->foreignUuid('test_id')->constrained('lab_test_catalogs')->onDelete('restrict');
                $table->enum('status', array_column(LabRequestItemStatus::cases(), 'value'))->default(LabRequestItemStatus::PENDING->value);
                $table->decimal('price', 10, 2)->default(0);
                $table->boolean('is_external')->default(false);
                $table->string('external_lab_name', 100)->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['request_id', 'test_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_request_items');
        Schema::dropIfExists('lab_requests');
    }
};
