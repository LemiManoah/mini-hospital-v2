<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_visits', function (Blueprint $table): void {
            if (! Schema::hasColumn('patient_visits', 'appointment_id')) {
                $table->foreignUuid('appointment_id')
                    ->nullable()
                    ->after('doctor_id')
                    ->constrained('appointments')
                    ->nullOnDelete();
                $table->index('appointment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_visits', function (Blueprint $table): void {
            if (Schema::hasColumn('patient_visits', 'appointment_id')) {
                $table->dropConstrainedForeignId('appointment_id');
            }
        });
    }
};
