<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_test_result_parameters', function (Blueprint $table): void {
            $table->string('gender', 20)->nullable()->after('unit'); // male, female, both
            $table->unsignedInteger('age_min')->nullable()->after('gender');
            $table->unsignedInteger('age_max')->nullable()->after('age_min');
        });
    }

    public function down(): void
    {
        Schema::table('lab_test_result_parameters', function (Blueprint $table): void {
            $table->dropColumn(['gender', 'age_min', 'age_max']);
        });
    }
};
