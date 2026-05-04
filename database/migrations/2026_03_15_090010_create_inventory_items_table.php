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
        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name', 200)->index();
                $table->string('generic_name', 200)->nullable()->index();
                $table->string('brand_name', 200)->nullable();
                $table->string('item_type', 50)->index();
                $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->enum('category', array_column(DrugCategory::cases(), 'value'))->nullable();
                $table->enum('dosage_form', array_column(DrugDosageForm::cases(), 'value'))->nullable();
                $table->string('strength', 50)->nullable();
                $table->string('manufacturer', 100)->nullable();
                $table->boolean('is_controlled')->default(false)->index();
                $table->string('schedule_class', 10)->nullable();
                $table->json('therapeutic_classes')->nullable();
                $table->text('contraindications')->nullable();
                $table->text('interactions')->nullable();
                $table->text('side_effects')->nullable();
                $table->boolean('expires')->default(false)->index();
                $table->text('description')->nullable();
                $table->decimal('minimum_stock_level', 14, 3)->default(0);
                $table->decimal('reorder_level', 14, 3)->default(0);
                $table->decimal('default_purchase_price', 14, 2)->nullable();
                $table->decimal('default_selling_price', 14, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'item_type', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
