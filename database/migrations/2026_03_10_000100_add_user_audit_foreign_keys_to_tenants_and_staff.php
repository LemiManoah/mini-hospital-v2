<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreign('created_by', 'tenants_created_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'tenants_updated_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->foreign('created_by', 'staff_created_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'staff_updated_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign('tenants_created_by_foreign');
            $table->dropForeign('tenants_updated_by_foreign');
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropForeign('staff_created_by_foreign');
            $table->dropForeign('staff_updated_by_foreign');
        });
    }
};
