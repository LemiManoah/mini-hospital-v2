<?php

declare(strict_types=1);

use App\Enums\BillingStatus;
use App\Enums\PayerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_billings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('facility_branch_id')->index();
            $table->uuid('patient_visit_id')->unique();
            $table->uuid('visit_payer_id');
            $table->uuid('insurance_company_id')->nullable()->index();
            $table->uuid('insurance_package_id')->nullable()->index();
            $table->string('invoice_number', 50)->nullable()->unique();
            $table->enum('payer_type', array_column(PayerType::cases(), 'value'))->default(PayerType::CASH->value);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->enum('status', array_column(BillingStatus::cases(), 'value'))->default(BillingStatus::PENDING->value);
            $table->timestamp('billed_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('facility_branch_id')->references('id')->on('facility_branches');
            $table->foreign('patient_visit_id')->references('id')->on('patient_visits');
            $table->foreign('visit_payer_id')->references('id')->on('visit_payers');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
            $table->foreign('insurance_package_id')->references('id')->on('insurance_packages');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_billings');
    }
};
