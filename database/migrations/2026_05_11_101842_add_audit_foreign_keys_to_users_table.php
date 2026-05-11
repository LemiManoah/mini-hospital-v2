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
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'created_by')) {
                $table->uuid('created_by')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'updated_by')) {
                $table->uuid('updated_by')->nullable()->index();
            }
        });

        $foreignKeys = collect(Schema::getForeignKeys('users'));

        Schema::table('users', function (Blueprint $table) use ($foreignKeys): void {
            if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'users_created_by_foreign')) {
                $table->foreign('created_by', 'users_created_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if ($foreignKeys->doesntContain(fn (array $foreignKey): bool => $foreignKey['name'] === 'users_updated_by_foreign')) {
                $table->foreign('updated_by', 'users_updated_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys('users'));

        Schema::table('users', function (Blueprint $table) use ($foreignKeys): void {
            if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'users_created_by_foreign')) {
                $table->dropForeign('users_created_by_foreign');
            }

            if ($foreignKeys->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'users_updated_by_foreign')) {
                $table->dropForeign('users_updated_by_foreign');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
