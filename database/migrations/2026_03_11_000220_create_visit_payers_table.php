<?php

declare(strict_types=1);

use App\Enums\PayerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visit_payers')) {
            Schema::create('visit_payers', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('patient_visit_id')->constrained('patient_visits')->onDelete('cascade');
                $table->enum('billing_type', array_column(PayerType::cases(), 'value'))->default(PayerType::CASH->value);
                $table->foreignUuid('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
                $table->foreignUuid('insurance_package_id')->nullable()->constrained('insurance_packages')->nullOnDelete();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['patient_visit_id']);
                $table->index(['tenant_id', 'billing_type']);
                $table->index(['insurance_company_id', 'insurance_package_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_payers');
    }
};
