<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('country_name', 100)->unique();
            $table->string('country_code', 10)->unique();
            $table->string('dial_code', 10);
            $table->string('currency', 10);
            $table->string('currency_symbol', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
