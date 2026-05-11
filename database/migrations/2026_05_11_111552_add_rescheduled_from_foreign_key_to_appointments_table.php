<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('appointments')
            || ! Schema::hasColumn('appointments', 'rescheduled_from_appointment_id')
        ) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys('appointments'));

        Schema::table('appointments', function (Blueprint $table) use ($foreignKeys): void {
            if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'appointments_rescheduled_from_appointment_id_foreign')) {
                $table->foreign('rescheduled_from_appointment_id', 'appointments_rescheduled_from_appointment_id_foreign')
                    ->references('id')
                    ->on('appointments')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys('appointments'));

        Schema::table('appointments', function (Blueprint $table) use ($foreignKeys): void {
            if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'appointments_rescheduled_from_appointment_id_foreign')) {
                $table->dropForeign('appointments_rescheduled_from_appointment_id_foreign');
            }
        });
    }
};
