<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prescriptions') || ! Schema::hasColumn('prescriptions', 'consultation_id')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->uuid('consultation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prescriptions') || ! Schema::hasColumn('prescriptions', 'consultation_id')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->uuid('consultation_id')->nullable(false)->change();
        });
    }
};
