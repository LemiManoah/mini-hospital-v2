<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_branch_currencies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->cascadeOnDelete();
            $table->foreignUuid('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['facility_branch_id', 'currency_id']);
            $table->index(['tenant_id', 'facility_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_branch_currencies');
    }
};
