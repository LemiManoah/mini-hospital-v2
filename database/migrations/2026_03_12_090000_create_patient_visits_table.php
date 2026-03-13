<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_visits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->string('visit_number', 50)->comment('Unique visit identifier');
            $table->enum('visit_type', array_column(App\Enums\VisitType::cases(), 'value'))->default(App\Enums\VisitType::OPD_CONSULTATION->value);
            $table->enum('status', array_column(App\Enums\VisitStatus::cases(), 'value'))->default(App\Enums\VisitStatus::REGISTERED->value);
            $table->foreignUuid('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignUuid('doctor_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->boolean('is_emergency')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'created_at']);
            $table->index(['tenant_id', 'visit_number']);
            $table->index('status');
            $table->index('facility_branch_id');
            $table->index('clinic_id');
            $table->index('doctor_id');
            $table->unique(['tenant_id', 'visit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_visits');
    }
};
