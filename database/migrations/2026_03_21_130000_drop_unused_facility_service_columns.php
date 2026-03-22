<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('facility_services')
            ->where('is_billable', true)
            ->whereNull('charge_master_id')
            ->update(['charge_master_id' => DB::raw('id')]);

        Schema::table('facility_services', function (Blueprint $table): void {
            $table->dropColumn(['department_name', 'default_instructions']);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facility_services', function (Blueprint $table): void {
            $table->string('department_name', 100)->nullable()->after('category');
            $table->text('default_instructions')->nullable()->after('description');
        });
    }
};
