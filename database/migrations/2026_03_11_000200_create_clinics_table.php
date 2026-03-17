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
        if (! Schema::hasTable('clinics')) {
            Schema::create('clinics', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained();
                $table->foreignUuid('branch_id')->constrained('facility_branches');
                $table->string('clinic_code', 20);
                $table->string('clinic_name', 100);
                $table->foreignUuid('department_id')->constrained();
                $table->string('location', 255)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('status')->default(GeneralStatus::ACTIVE->value);
                $table->foreignUuid('created_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();

                $table->unique(['tenant_id', 'clinic_code']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
