<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_catalogs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('test_code', 20);
            $table->string('test_name', 200);
            $table->string('category', 50)->index();
            $table->string('sub_category', 50)->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('specimen_type', 50);
            $table->string('container_type', 50)->nullable();
            $table->decimal('volume_required_ml', 5, 2)->nullable();
            $table->string('storage_requirements', 100)->nullable();
            $table->integer('turnaround_time_minutes')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->boolean('requires_fasting')->default(false);
            $table->json('reference_ranges')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'test_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_catalogs');
    }
};
