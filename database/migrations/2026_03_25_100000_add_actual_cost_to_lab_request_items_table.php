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
            if (! Schema::hasColumn('lab_request_items', 'actual_cost')) {
                $table->decimal('actual_cost', 10, 2)->default(0)->after('price');
            }

            if (! Schema::hasColumn('lab_request_items', 'costed_at')) {
                $table->timestamp('costed_at')->nullable()->after('actual_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table): void {
            if (Schema::hasColumn('lab_request_items', 'costed_at')) {
                $table->dropColumn('costed_at');
            }

            if (Schema::hasColumn('lab_request_items', 'actual_cost')) {
                $table->dropColumn('actual_cost');
            }
        });
    }
};
