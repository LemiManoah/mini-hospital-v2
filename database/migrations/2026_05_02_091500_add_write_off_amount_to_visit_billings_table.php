<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_billings', function (Blueprint $table): void {
            $table->decimal('write_off_amount', 15, 2)->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('visit_billings', function (Blueprint $table): void {
            $table->dropColumn('write_off_amount');
        });
    }
};
