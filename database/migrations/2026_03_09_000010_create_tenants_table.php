<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
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
            $table->enum('facility_level', array_column(FacilityLevel::cases(), 'value'));
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            // Audit fields are plain UUIDs here; FKs are added in a later migration after users exists.
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();

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
