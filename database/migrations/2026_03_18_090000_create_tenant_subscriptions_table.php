<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('subscription_package_id')->constrained('subscription_packages');
                $table->enum('status', array_column(SubscriptionStatus::cases(), 'value'))
                    ->default(SubscriptionStatus::TRIAL->value);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('current_period_starts_at')->nullable();
                $table->timestamp('current_period_ends_at')->nullable();
                $table->string('checkout_provider', 50)->nullable();
                $table->string('checkout_reference', 120)->nullable();
                $table->text('checkout_url')->nullable();
                $table->json('meta')->nullable();
                $table->uuid('created_by')->nullable()->index();
                $table->uuid('updated_by')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
