<?php

declare(strict_types=1);

use App\Enums\VisitChargeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_charges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('facility_branch_id')->index();
            $table->uuid('visit_billing_id')->index();
            $table->uuid('patient_visit_id')->index();
            $table->uuidMorphs('source');
            $table->string('charge_code', 50)->nullable()->index();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->enum('status', array_column(VisitChargeStatus::cases(), 'value'))->default(VisitChargeStatus::ACTIVE->value);
            $table->timestamp('charged_at')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('facility_branch_id')->references('id')->on('facility_branches');
            $table->foreign('visit_billing_id')->references('id')->on('visit_billings');
            $table->foreign('patient_visit_id')->references('id')->on('patient_visits');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_charges');
    }
};
