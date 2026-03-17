<?php

declare(strict_types=1);

use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
                $table->foreignUuid('consultation_id')->constrained('consultations')->onDelete('cascade');
                $table->foreignUuid('prescribed_by')->constrained('staff')->onDelete('restrict');
                $table->timestamp('prescription_date')->useCurrent();
                $table->boolean('is_discharge_medication')->default(false);
                $table->boolean('is_long_term')->default(false);
                $table->string('primary_diagnosis', 255)->nullable();
                $table->text('pharmacy_notes')->nullable();
                $table->enum('status', array_column(PrescriptionStatus::cases(), 'value'))->default(PrescriptionStatus::PENDING->value);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prescription_items')) {
            Schema::create('prescription_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('prescription_id')->constrained('prescriptions')->onDelete('cascade');
                $table->foreignUuid('drug_id')->constrained('drugs')->onDelete('restrict');
                $table->string('dosage', 50);
                $table->string('frequency', 50);
                $table->string('route', 50);
                $table->integer('duration_days');
                $table->integer('quantity');
                $table->text('instructions')->nullable();
                $table->boolean('is_prn')->default(false);
                $table->string('prn_reason', 100)->nullable();
                $table->boolean('is_external_pharmacy')->default(false);
                $table->enum('status', array_column(PrescriptionItemStatus::cases(), 'value'))->default(PrescriptionItemStatus::PENDING->value);
                $table->timestamp('dispensed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
