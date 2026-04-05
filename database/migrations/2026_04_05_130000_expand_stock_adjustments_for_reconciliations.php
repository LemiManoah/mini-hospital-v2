<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table): void {
            $table->foreignUuid('submitted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignUuid('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->foreignUuid('approved_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->foreignUuid('rejected_by')->nullable()->after('approval_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table): void {
            $table->decimal('expected_quantity', 14, 3)->nullable()->after('inventory_batch_id');
            $table->decimal('actual_quantity', 14, 3)->nullable()->after('expected_quantity');
            $table->decimal('variance_quantity', 14, 3)->nullable()->after('actual_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustment_items', function (Blueprint $table): void {
            $table->dropColumn(['expected_quantity', 'actual_quantity', 'variance_quantity']);
        });

        Schema::table('stock_adjustments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('submitted_at');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'review_notes']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'approval_notes']);
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });
    }
};
