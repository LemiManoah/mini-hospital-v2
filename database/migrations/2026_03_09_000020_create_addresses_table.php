<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('city', 100)->index();
            $table->string('district', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();

            $table->softDeletes();
            // Phase 2 introduces the staff table; keep nullable UUIDs for now.
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
