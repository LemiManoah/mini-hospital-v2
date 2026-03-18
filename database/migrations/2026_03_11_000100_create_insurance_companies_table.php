<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
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
        if (! Schema::hasTable('insurance_companies')) {
            Schema::create('insurance_companies', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name', 150)->index();
                $table->string('email')->nullable()->index();
                $table->string('main_contact', 20)->nullable()->index();
                $table->string('other_contact', 20)->nullable();
                $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
                $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value);

                // Audit fields
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

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
        Schema::dropIfExists('insurance_companies');
    }
};
