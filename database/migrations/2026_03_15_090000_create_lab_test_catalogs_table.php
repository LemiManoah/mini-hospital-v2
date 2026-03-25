<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_test_categories')) {
            Schema::create('lab_test_categories', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'name']);
            });

            DB::table('lab_test_categories')->insert([
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Hematology',
                    'description' => 'Blood cell counts and related investigations.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Chemistry',
                    'description' => 'Clinical chemistry and biochemistry tests.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Microbiology',
                    'description' => 'Culture and organism-focused investigations.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Parasitology',
                    'description' => 'Parasite-focused tests and microscopy.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Serology',
                    'description' => 'Antigen and antibody-based testing.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (! Schema::hasTable('specimen_types')) {
            Schema::create('specimen_types', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'name']);
            });

            DB::table('specimen_types')->insert([
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Blood',
                    'description' => 'Whole blood or blood-derived specimen.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Urine',
                    'description' => 'Urine sample.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Stool',
                    'description' => 'Stool sample.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Swab',
                    'description' => 'Swab specimen.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'name' => 'Serum',
                    'description' => 'Separated serum specimen.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (! Schema::hasTable('result_types')) {
            Schema::create('result_types', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
                $table->string('code', 50);
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
            });

            DB::table('result_types')->insert([
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'code' => 'free_entry',
                    'name' => 'Free Entry',
                    'description' => 'Single free-text or numeric result entry.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'code' => 'defined_option',
                    'name' => 'Defined Option',
                    'description' => 'Select from predefined qualitative options.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'code' => 'parameter_panel',
                    'name' => 'Parameter Panel',
                    'description' => 'Capture multiple analytes or parameters.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => (string) str()->uuid(),
                    'tenant_id' => null,
                    'code' => 'culture',
                    'name' => 'Culture',
                    'description' => 'Culture-oriented workflow and narrative output.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (! Schema::hasTable('lab_test_catalogs')) {
            Schema::create('lab_test_catalogs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('test_code', 20);
                $table->string('test_name', 200);
                $table->foreignUuid('lab_test_category_id')->constrained('lab_test_categories')->onDelete('cascade');
                $table->foreignUuid('result_type_id')->constrained('result_types')->onDelete('cascade');
                $table->text('description')->nullable();
                $table->decimal('base_price', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'test_code']);
            });
        }

        if (! Schema::hasTable('lab_test_catalog_specimen_type')) {
            Schema::create('lab_test_catalog_specimen_type', function (Blueprint $table): void {
                $table->foreignUuid('lab_test_catalog_id')->constrained('lab_test_catalogs')->onDelete('cascade');
                $table->foreignUuid('specimen_type_id')->constrained('specimen_types');
                $table->timestamps();

                $table->unique(['lab_test_catalog_id', 'specimen_type_id'], 'lab_test_catalog_specimen_unique');
            });
        }

        if (! Schema::hasTable('lab_test_result_options')) {
            Schema::create('lab_test_result_options', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_test_catalog_id')->constrained('lab_test_catalogs')->onDelete('cascade');
                $table->string('label', 150);
                $table->unsignedInteger('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lab_test_result_parameters')) {
            Schema::create('lab_test_result_parameters', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_test_catalog_id')->constrained('lab_test_catalogs')->onDelete('cascade');
                $table->string('label', 150);
                $table->string('unit', 50)->nullable();
                $table->string('reference_range', 120)->nullable();
                $table->string('value_type', 30)->default('numeric');
                $table->unsignedInteger('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_result_parameters');
        Schema::dropIfExists('lab_test_result_options');
        Schema::dropIfExists('lab_test_catalog_specimen_type');
        Schema::dropIfExists('lab_test_catalogs');
        Schema::dropIfExists('result_types');
        Schema::dropIfExists('specimen_types');
        Schema::dropIfExists('lab_test_categories');
    }
};
