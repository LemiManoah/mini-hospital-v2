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
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('domain', 100)->unique()->nullable()->comment('For custom subdomains');
            $table->boolean('has_branches')->default(false);
            $table->string('logo')->nullable();
            $table->string('stamp')->nullable();
            $table->foreignUuid('subscription_package_id')->constrained('subscription_packages');
            $table->enum('status', array_column(GeneralStatus::cases(), 'value'))->default(GeneralStatus::ACTIVE->value);
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            
            // Audit fields
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
