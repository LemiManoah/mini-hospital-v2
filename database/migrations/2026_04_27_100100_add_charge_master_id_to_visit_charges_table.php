<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_charges', function (Blueprint $table): void {
            if (! Schema::hasColumn('visit_charges', 'charge_master_id')) {
                $table->foreignUuid('charge_master_id')->nullable()->after('source_id')->constrained('charge_masters')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('visit_charges', function (Blueprint $table): void {
            if (Schema::hasColumn('visit_charges', 'charge_master_id')) {
                $table->dropConstrainedForeignId('charge_master_id');
            }
        });
    }
};
