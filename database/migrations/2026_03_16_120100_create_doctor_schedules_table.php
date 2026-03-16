<?php

declare(strict_types=1);

use App\Enums\ScheduleDay;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->foreignUuid('doctor_id')->constrained('staff')->onDelete('cascade');
            $table->foreignUuid('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->enum('day_of_week', array_column(ScheduleDay::cases(), 'value'))->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15);
            $table->unsignedSmallInteger('max_patients')->default(20);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['doctor_id', 'clinic_id', 'day_of_week', 'start_time'],
                'doctor_schedules_unique_slot',
            );
            $table->index(['facility_branch_id', 'doctor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
