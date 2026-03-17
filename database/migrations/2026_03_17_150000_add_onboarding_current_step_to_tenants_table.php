<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || Schema::hasColumn('tenants', 'onboarding_current_step')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('onboarding_current_step', 30)
                ->default('profile')
                ->after('onboarding_completed_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'onboarding_current_step')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('onboarding_current_step');
        });
    }
};
