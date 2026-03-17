<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_packages')) {
            Schema::create('subscription_packages', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name', 100)->unique();
                $table->unsignedInteger('users')->unique();
                $table->decimal('price', 12, 2)->default(0);
                $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
