<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('lab_request_items', 'received_by')) {
                $table->foreignUuid('received_by')->nullable()->after('external_lab_name')->constrained('staff')->nullOnDelete();
            }

            if (! Schema::hasColumn('lab_request_items', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by');
            }

            if (! Schema::hasColumn('lab_request_items', 'result_entered_by')) {
                $table->foreignUuid('result_entered_by')->nullable()->after('received_at')->constrained('staff')->nullOnDelete();
            }

            if (! Schema::hasColumn('lab_request_items', 'result_entered_at')) {
                $table->timestamp('result_entered_at')->nullable()->after('result_entered_by');
            }

            if (! Schema::hasColumn('lab_request_items', 'reviewed_by')) {
                $table->foreignUuid('reviewed_by')->nullable()->after('result_entered_at')->constrained('staff')->nullOnDelete();
            }

            if (! Schema::hasColumn('lab_request_items', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }

            if (! Schema::hasColumn('lab_request_items', 'approved_by')) {
                $table->foreignUuid('approved_by')->nullable()->after('reviewed_at')->constrained('staff')->nullOnDelete();
            }

            if (! Schema::hasColumn('lab_request_items', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        if (! Schema::hasTable('lab_result_entries')) {
            Schema::create('lab_result_entries', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_request_item_id')->constrained('lab_request_items')->onDelete('cascade');
                $table->foreignUuid('entered_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('entered_at')->nullable();
                $table->foreignUuid('reviewed_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignUuid('approved_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignUuid('released_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('released_at')->nullable();
                $table->text('result_notes')->nullable();
                $table->text('review_notes')->nullable();
                $table->text('approval_notes')->nullable();
                $table->timestamps();

                $table->unique('lab_request_item_id');
            });
        }

        if (! Schema::hasTable('lab_result_values')) {
            Schema::create('lab_result_values', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_result_entry_id')->constrained('lab_result_entries')->onDelete('cascade');
                $table->foreignUuid('lab_test_result_parameter_id')->nullable()->constrained('lab_test_result_parameters')->nullOnDelete();
                $table->string('label', 150);
                $table->decimal('value_numeric', 12, 2)->nullable();
                $table->text('value_text')->nullable();
                $table->string('unit', 50)->nullable();
                $table->string('reference_range', 120)->nullable();
                $table->unsignedInteger('sort_order')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_values');
        Schema::dropIfExists('lab_result_entries');

        Schema::table('lab_request_items', function (Blueprint $table): void {
            foreach ([
                'approved_by',
                'reviewed_by',
                'result_entered_by',
                'received_by',
            ] as $foreignColumn) {
                if (Schema::hasColumn('lab_request_items', $foreignColumn)) {
                    $table->dropConstrainedForeignId($foreignColumn);
                }
            }

            foreach ([
                'approved_at',
                'reviewed_at',
                'result_entered_at',
                'received_at',
            ] as $column) {
                if (Schema::hasColumn('lab_request_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
