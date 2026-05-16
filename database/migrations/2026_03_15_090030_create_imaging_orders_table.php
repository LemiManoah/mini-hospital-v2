<?php

declare(strict_types=1);

use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingOrderStatus;
use App\Enums\ImagingPriority;
use App\Enums\PregnancyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('imaging_study_catalogs')) {
            Schema::create('imaging_study_catalogs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
                $table->string('code', 50);
                $table->string('name', 150)->index();
                $table->enum('modality', array_column(ImagingModality::cases(), 'value'));
                $table->string('body_part', 100)->nullable();
                $table->foreignUuid('charge_master_id')->nullable()->constrained('charge_masters')->nullOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
                $table->index(['modality', 'body_part']);
            });
        }

        if (! Schema::hasTable('imaging_orders')) {
            Schema::create('imaging_orders', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
                $table->foreignUuid('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
                $table->foreignUuid('imaging_study_catalog_id')->nullable()->constrained('imaging_study_catalogs')->nullOnDelete();
                $table->foreignUuid('requested_by')->constrained('staff')->onDelete('restrict');
                $table->enum('modality', array_column(ImagingModality::cases(), 'value'));
                $table->string('body_part', 100);
                $table->enum('laterality', array_column(ImagingLaterality::cases(), 'value'))->default(ImagingLaterality::NOT_APPLICABLE->value);
                $table->text('clinical_history');
                $table->text('indication');
                $table->enum('priority', array_column(ImagingPriority::cases(), 'value'))->default(ImagingPriority::ROUTINE->value);
                $table->enum('status', array_column(ImagingOrderStatus::cases(), 'value'))->default(ImagingOrderStatus::REQUESTED->value);
                $table->timestamp('scheduled_date')->nullable();
                $table->foreignUuid('scheduled_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->boolean('requires_contrast')->default(false);
                $table->string('contrast_allergy_status', 50)->nullable();
                $table->enum('pregnancy_status', array_column(PregnancyStatus::cases(), 'value'))->default(PregnancyStatus::UNKNOWN->value);
                $table->decimal('radiation_dose_msv', 8, 3)->nullable();
                $table->timestamps();

                $table->index(['modality', 'status']);
                $table->index('scheduled_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_orders');
        Schema::dropIfExists('imaging_study_catalogs');
    }
};
