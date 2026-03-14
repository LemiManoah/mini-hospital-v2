<?php

declare(strict_types=1);

use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\ImagingRequestStatus;
use App\Enums\PregnancyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
            $table->foreignUuid('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignUuid('requested_by')->constrained('staff')->onDelete('restrict');
            $table->enum('modality', array_column(ImagingModality::cases(), 'value'));
            $table->string('body_part', 100);
            $table->enum('laterality', array_column(ImagingLaterality::cases(), 'value'))->default(ImagingLaterality::NOT_APPLICABLE->value);
            $table->text('clinical_history');
            $table->text('indication');
            $table->enum('priority', array_column(ImagingPriority::cases(), 'value'))->default(ImagingPriority::ROUTINE->value);
            $table->enum('status', array_column(ImagingRequestStatus::cases(), 'value'))->default(ImagingRequestStatus::REQUESTED->value);
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

    public function down(): void
    {
        Schema::dropIfExists('imaging_requests');
    }
};
