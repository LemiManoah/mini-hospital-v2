<?php

declare(strict_types=1);

use App\Enums\DispensingRecordStatus;
use App\Enums\PrescriptionItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensing_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignUuid('inventory_location_id')->constrained('inventory_locations')->onDelete('restrict');
            $table->string('dispense_number', 50)->unique();
            $table->foreignUuid('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispensed_at');
            $table->text('notes')->nullable();
            $table->enum('status', array_column(DispensingRecordStatus::cases(), 'value'))
                ->default(DispensingRecordStatus::DRAFT->value)
                ->index();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'prescription_id'], 'dispensing_records_lookup');
        });

        Schema::create('dispensing_record_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispensing_record_id')->constrained('dispensing_records')->onDelete('cascade');
            $table->foreignUuid('prescription_item_id')->constrained('prescription_items')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->foreignUuid('substitution_inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('prescribed_quantity', 14, 3);
            $table->decimal('dispensed_quantity', 14, 3)->default(0);
            $table->decimal('balance_quantity', 14, 3)->default(0);
            $table->enum('dispense_status', array_column(PrescriptionItemStatus::cases(), 'value'))
                ->default(PrescriptionItemStatus::PENDING->value);
            $table->boolean('external_pharmacy')->default(false);
            $table->text('external_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['dispensing_record_id', 'prescription_item_id'], 'dispensing_record_items_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensing_record_items');
        Schema::dropIfExists('dispensing_records');
    }
};
