<?php

declare(strict_types=1);

use App\Enums\ScheduleExceptionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedule_exceptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->foreignUuid('doctor_id')->constrained('staff')->onDelete('cascade');
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->date('exception_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', array_column(ScheduleExceptionType::cases(), 'value'))->index();
            $table->text('reason')->nullable();
            $table->boolean('is_all_day')->default(true)->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['facility_branch_id', 'doctor_id', 'exception_date'], 'doctor_schedule_exceptions_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedule_exceptions');
    }
};
