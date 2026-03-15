<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vital_signs')) {
            Schema::create('vital_signs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('triage_id')->constrained('triage_records')->onDelete('cascade');
                $table->timestamp('recorded_at')->useCurrent();
                $table->decimal('temperature', 4, 1)->nullable();
                $table->enum('temperature_unit', ['celsius', 'fahrenheit'])->default('celsius');
                $table->integer('pulse_rate')->nullable();
                $table->integer('respiratory_rate')->nullable();
                $table->integer('systolic_bp')->nullable();
                $table->integer('diastolic_bp')->nullable();
                $table->integer('map')->nullable();
                $table->decimal('oxygen_saturation', 5, 2)->nullable();
                $table->boolean('on_supplemental_oxygen')->default(false);
                $table->string('oxygen_delivery_method', 50)->nullable();
                $table->decimal('oxygen_flow_rate', 4, 1)->nullable();
                $table->decimal('blood_glucose', 5, 2)->nullable();
                $table->enum('blood_glucose_unit', ['mg_dl', 'mmol_l'])->default('mg_dl');
                $table->integer('pain_score')->nullable();
                $table->decimal('height_cm', 5, 2)->nullable();
                $table->decimal('weight_kg', 5, 2)->nullable();
                $table->decimal('bmi', 5, 2)->nullable();
                $table->decimal('head_circumference_cm', 5, 2)->nullable();
                $table->decimal('chest_circumference_cm', 5, 2)->nullable();
                $table->decimal('muac_cm', 5, 2)->nullable();
                $table->string('capillary_refill', 20)->nullable();
                $table->foreignUuid('recorded_by')->constrained('staff')->onDelete('restrict');
                $table->timestamps();

                $table->index(['triage_id', 'recorded_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
