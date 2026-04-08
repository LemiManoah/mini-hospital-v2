<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_specimens', function (Blueprint $table): void {
            if (! Schema::hasColumn('lab_specimens', 'rejected_by')) {
                $table->foreignUuid('rejected_by')->nullable()->after('collected_at')->constrained('staff')->nullOnDelete();
            }

            if (! Schema::hasColumn('lab_specimens', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by')->index();
            }

            if (! Schema::hasColumn('lab_specimens', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_specimens', function (Blueprint $table): void {
            if (Schema::hasColumn('lab_specimens', 'rejected_by')) {
                $table->dropConstrainedForeignId('rejected_by');
            }

            foreach (['rejected_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('lab_specimens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
