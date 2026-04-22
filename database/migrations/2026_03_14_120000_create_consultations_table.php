<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultations')) {
            Schema::create('consultations', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
                $table->foreignUuid('visit_id')->unique()->constrained('patient_visits')->onDelete('cascade');
                $table->foreignUuid('doctor_id')->constrained('staff')->onDelete('restrict');
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->string('chief_complaint', 500)->nullable();
                $table->text('history_of_present_illness')->nullable();
                $table->text('review_of_systems')->nullable();
                $table->text('past_medical_history_summary')->nullable();
                $table->text('family_history')->nullable();
                $table->text('social_history')->nullable();
                $table->string('subjective_notes', 1000)->nullable();
                $table->text('objective_findings')->nullable();
                $table->text('assessment')->nullable();
                $table->text('plan')->nullable();
                $table->string('primary_diagnosis')->nullable();
                $table->string('primary_icd10_code', 10)->nullable();
                $table->json('secondary_diagnoses')->nullable();
                $table->string('outcome', 50)->nullable();
                $table->text('follow_up_instructions')->nullable();
                $table->integer('follow_up_days')->nullable();
                $table->boolean('is_referred')->default(false);
                $table->string('referred_to_department', 100)->nullable();
                $table->string('referred_to_facility', 100)->nullable();
                $table->text('referral_reason')->nullable();
                $table->timestamps();

                $table->index('doctor_id');
                $table->index('primary_icd10_code');
                $table->index('started_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
