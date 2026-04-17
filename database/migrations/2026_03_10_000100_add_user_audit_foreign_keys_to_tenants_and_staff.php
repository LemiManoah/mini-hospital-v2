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
        if (
            Schema::hasTable('tenants')
            && Schema::hasColumn('tenants', 'created_by')
            && Schema::hasColumn('tenants', 'updated_by')
        ) {
            $foreignKeys = collect(Schema::getForeignKeys('tenants'));

            Schema::table('tenants', function (Blueprint $table) use ($foreignKeys): void {
                if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'tenants_created_by_foreign')) {
                    $table->foreign('created_by', 'tenants_created_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }

                if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'tenants_updated_by_foreign')) {
                    $table->foreign('updated_by', 'tenants_updated_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        }

        if (
            Schema::hasTable('staff')
            && Schema::hasColumn('staff', 'created_by')
            && Schema::hasColumn('staff', 'updated_by')
        ) {
            $foreignKeys = collect(Schema::getForeignKeys('staff'));

            Schema::table('staff', function (Blueprint $table) use ($foreignKeys): void {
                if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_created_by_foreign')) {
                    $table->foreign('created_by', 'staff_created_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }

                if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_updated_by_foreign')) {
                    $table->foreign('updated_by', 'staff_updated_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        }

        if (
            Schema::hasTable('staff_positions')
            && Schema::hasColumn('staff_positions', 'created_by')
            && Schema::hasColumn('staff_positions', 'updated_by')
        ) {
            $foreignKeys = collect(Schema::getForeignKeys('staff_positions'));

            Schema::table('staff_positions', function (Blueprint $table) use ($foreignKeys): void {
                if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_positions_created_by_foreign')) {
                    $table->foreign('created_by', 'staff_positions_created_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }

                if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_positions_updated_by_foreign')) {
                    $table->foreign('updated_by', 'staff_positions_updated_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenants')) {
            $foreignKeys = collect(Schema::getForeignKeys('tenants'));

            Schema::table('tenants', function (Blueprint $table) use ($foreignKeys): void {
                if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'tenants_created_by_foreign')) {
                    $table->dropForeign('tenants_created_by_foreign');
                }

                if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'tenants_updated_by_foreign')) {
                    $table->dropForeign('tenants_updated_by_foreign');
                }
            });
        }

        if (Schema::hasTable('staff')) {
            $foreignKeys = collect(Schema::getForeignKeys('staff'));

            Schema::table('staff', function (Blueprint $table) use ($foreignKeys): void {
                if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_created_by_foreign')) {
                    $table->dropForeign('staff_created_by_foreign');
                }

                if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_updated_by_foreign')) {
                    $table->dropForeign('staff_updated_by_foreign');
                }
            });
        }

        if (Schema::hasTable('staff_positions')) {
            $foreignKeys = collect(Schema::getForeignKeys('staff_positions'));

            Schema::table('staff_positions', function (Blueprint $table) use ($foreignKeys): void {
                if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_positions_created_by_foreign')) {
                    $table->dropForeign('staff_positions_created_by_foreign');
                }

                if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'staff_positions_updated_by_foreign')) {
                    $table->dropForeign('staff_positions_updated_by_foreign');
                }
            });
        }
    }
};
