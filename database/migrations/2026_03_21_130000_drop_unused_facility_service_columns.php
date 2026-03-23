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

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('facility_services', 'department_name') ? 'department_name' : null,
            Schema::hasColumn('facility_services', 'default_instructions') ? 'default_instructions' : null,
        ]));

        Schema::table('facility_services', function (Blueprint $table): void {
            if (! Schema::hasColumn('facility_services', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('facility_services', 'selling_price')) {
                $table->decimal('selling_price', 10, 2)->nullable();
            }
        });

        if ($columnsToDrop !== []) {
            Schema::table('facility_services', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        Schema::table('facility_services', function (Blueprint $table): void {
            if (! Schema::hasColumn('facility_services', 'department_name')) {
                $table->string('department_name', 100)->nullable()->after('category');
            }

            if (! Schema::hasColumn('facility_services', 'default_instructions')) {
                $table->text('default_instructions')->nullable()->after('description');
            }
        });
    }
};
