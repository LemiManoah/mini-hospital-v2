<?php

declare(strict_types=1);

use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('drugs')) {
            Schema::create('drugs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('generic_name', 200)->index();
                $table->string('brand_name', 200)->nullable();
                $table->string('drug_code', 50);
                $table->enum('category', array_column(DrugCategory::cases(), 'value'))->default(DrugCategory::OTHER->value);
                $table->enum('dosage_form', array_column(DrugDosageForm::cases(), 'value'))->default(DrugDosageForm::OTHER->value);
                $table->string('strength', 50);
                $table->string('unit', 20);
                $table->string('manufacturer', 100)->nullable();
                $table->boolean('is_controlled')->default(false)->index();
                $table->string('schedule_class', 10)->nullable();
                $table->json('therapeutic_classes')->nullable();
                $table->text('contraindications')->nullable();
                $table->text('interactions')->nullable();
                $table->text('side_effects')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'drug_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
