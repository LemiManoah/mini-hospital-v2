<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('code', 10)->unique();
                $table->string('name', 100);
                $table->string('symbol', 10);
                $table->tinyInteger('decimal_places')->unsigned()->default(2);
                $table->string('symbol_position', 10)->default('before');
                $table->boolean('modifiable')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
