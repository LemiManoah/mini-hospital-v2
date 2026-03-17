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
        if (! Schema::hasTable('patient_allergies')) {
            Schema::create('patient_allergies', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('patient_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('allergen_id')->constrained('allergens')->onDelete('cascade');
                $table->enum('severity', ['mild', 'moderate', 'severe', 'life_threatening']);
                $table->enum('reaction', ['rash', 'anaphylaxis', 'breathing_difficulty', 'itching', 'swelling', 'other']);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamps();

                $table->index(['patient_id', 'allergen_id']);
                $table->index('severity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};
