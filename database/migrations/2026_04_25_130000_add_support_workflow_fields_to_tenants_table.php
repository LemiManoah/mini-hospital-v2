<?php

declare(strict_types=1);

use App\Enums\TenantSupportPriority;
use App\Enums\TenantSupportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->enum('support_status', array_column(TenantSupportStatus::cases(), 'value'))
                ->default(TenantSupportStatus::STABLE->value)
                ->after('onboarding_current_step')
                ->index();
            $table->enum('support_priority', array_column(TenantSupportPriority::cases(), 'value'))
                ->default(TenantSupportPriority::NORMAL->value)
                ->after('support_status')
                ->index();
            $table->timestamp('support_follow_up_at')->nullable()->after('support_priority');
            $table->timestamp('support_last_contacted_at')->nullable()->after('support_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'support_status',
                'support_priority',
                'support_follow_up_at',
                'support_last_contacted_at',
            ]);
        });
    }
};
