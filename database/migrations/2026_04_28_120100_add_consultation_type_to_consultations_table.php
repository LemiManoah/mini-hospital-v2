<?php

declare(strict_types=1);

use App\Enums\ConsultationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            if (! Schema::hasColumn('consultations', 'consultation_type')) {
                $table->enum('consultation_type', array_column(ConsultationType::cases(), 'value'))
                    ->nullable()
                    ->after('doctor_id')
                    ->index();
            }
        });

    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            if (Schema::hasColumn('consultations', 'consultation_type')) {
                $table->dropIndex(['consultation_type']);
                $table->dropColumn('consultation_type');
            }
        });
    }
};
