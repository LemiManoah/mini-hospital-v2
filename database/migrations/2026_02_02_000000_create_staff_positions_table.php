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
        if (! Schema::hasTable('staff_positions')) {
            Schema::create('staff_positions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);

                // Audit fields are added as plain UUIDs first to avoid a creation-order cycle
                // between staff_positions, staff, and users. Their foreign keys are added later.
                $table->uuid('created_by')->nullable()->index();
                $table->uuid('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_positions');
    }
};
