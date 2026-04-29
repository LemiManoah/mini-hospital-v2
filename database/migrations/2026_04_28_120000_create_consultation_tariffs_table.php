<?php

declare(strict_types=1);

use App\Enums\ConsultationType;
use App\Enums\VisitType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_tariffs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->enum('visit_type', array_column(VisitType::cases(), 'value'))->nullable();
            $table->enum('consultation_type', array_column(ConsultationType::cases(), 'value'))->index();
            $table->foreignUuid('facility_service_id')->constrained('facility_services')->onDelete('restrict');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'facility_branch_id', 'consultation_type'],
                'consultation_tariffs_branch_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_tariffs');
    }
};
