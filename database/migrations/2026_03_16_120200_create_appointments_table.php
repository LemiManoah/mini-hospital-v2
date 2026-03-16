<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignUuid('doctor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('appointment_category_id')->nullable()->constrained('appointment_categories')->nullOnDelete();
            $table->foreignUuid('appointment_mode_id')->nullable()->constrained('appointment_modes')->nullOnDelete();
            $table->date('appointment_date')->index();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->enum('status', array_column(AppointmentStatus::cases(), 'value'))
                ->default(AppointmentStatus::SCHEDULED->value)
                ->index();
            $table->text('reason_for_visit');
            $table->string('chief_complaint', 255)->nullable();
            $table->boolean('is_walk_in')->default(false);
            $table->unsignedInteger('queue_number')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignUuid('rescheduled_from_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['doctor_id', 'appointment_date']);
            $table->index(['patient_id', 'appointment_date']);
            $table->index(['appointment_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
