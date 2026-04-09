<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_result_entries')) {
            return;
        }

        Schema::table('lab_result_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('lab_result_entries', 'corrected_by')) {
                $table->foreignUuid('corrected_by')
                    ->nullable()
                    ->after('released_at')
                    ->constrained('staff')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('lab_result_entries', 'corrected_at')) {
                $table->timestamp('corrected_at')->nullable()->after('corrected_by');
            }

            if (! Schema::hasColumn('lab_result_entries', 'correction_reason')) {
                $table->text('correction_reason')->nullable()->after('approval_notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lab_result_entries')) {
            return;
        }

        Schema::table('lab_result_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('lab_result_entries', 'corrected_by')) {
                $table->dropConstrainedForeignId('corrected_by');
            }

            foreach (['corrected_at', 'correction_reason'] as $column) {
                if (Schema::hasColumn('lab_result_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
