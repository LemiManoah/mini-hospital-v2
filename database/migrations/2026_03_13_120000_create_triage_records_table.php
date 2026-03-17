<?php

declare(strict_types=1);

use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\MobilityStatus;
use App\Enums\TriageGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('triage_records')) {
            Schema::create('triage_records', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
                $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade')->unique();
                $table->foreignUuid('nurse_id')->constrained('staff')->onDelete('restrict');
                $table->timestamp('triage_datetime')->useCurrent();
                $table->enum('triage_grade', array_column(TriageGrade::cases(), 'value'));
                $table->enum('attendance_type', array_column(AttendanceType::cases(), 'value'));
                $table->integer('news_score')->nullable();
                $table->integer('pews_score')->nullable();
                $table->enum('conscious_level', array_column(ConsciousLevel::cases(), 'value'));
                $table->enum('mobility_status', array_column(MobilityStatus::cases(), 'value'));
                $table->text('chief_complaint');
                $table->text('history_of_presenting_illness')->nullable();
                $table->foreignUuid('assigned_clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
                $table->boolean('requires_priority')->default(false);
                $table->boolean('is_pediatric')->default(false);
                $table->boolean('poisoning_case')->default(false);
                $table->string('poisoning_agent', 100)->nullable();
                $table->boolean('snake_bite_case')->default(false);
                $table->string('referred_by', 100)->nullable();
                $table->text('nurse_notes')->nullable();
                $table->timestamps();

                $table->index('triage_grade');
                $table->index(['requires_priority', 'triage_datetime']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_records');
    }
};
