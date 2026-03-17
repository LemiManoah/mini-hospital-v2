<?php

declare(strict_types=1);

use App\Enums\AllergyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('allergens')) {
            Schema::create('allergens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('name', 100)->index();
                $table->text('description')->nullable();
                $table->enum('type', array_column(AllergyType::cases(), 'value'));

                $table->softDeletes();
                // Phase 2 introduces the staff table; keep nullable UUIDs for now.
                $table->uuid('created_by')->nullable()->index();
                $table->uuid('updated_by')->nullable()->index();
                $table->timestamps();

                $table->index('type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('allergens');
    }
};
