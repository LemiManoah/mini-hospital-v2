<?php

declare(strict_types=1);

use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanFrequencyUnit;
use App\Enums\PharmacyTreatmentPlanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
        Schema::create('pharmacy_treatment_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->foreignUuid('visit_id')->constrained('patient_visits')->onDelete('cascade');
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->date('start_date');
            $table->enum('frequency_unit', array_column(PharmacyTreatmentPlanFrequencyUnit::cases(), 'value'));
            $table->unsignedInteger('frequency_interval')->default(1);
            $table->unsignedInteger('total_authorized_cycles');
            $table->unsignedInteger('completed_cycles')->default(0);
            $table->date('next_refill_date')->nullable();
            $table->enum('status', array_column(PharmacyTreatmentPlanStatus::cases(), 'value'))
                ->default(PharmacyTreatmentPlanStatus::ACTIVE->value)
                ->index();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'branch_id', 'status', 'next_refill_date'],
                'pharmacy_treatment_plans_schedule_lookup',
            );
        });
        }

        if (! Schema::hasTable('currencies')) {
        Schema::create('pharmacy_treatment_plan_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_treatment_plan_id');
            $table->foreign('pharmacy_treatment_plan_id', 'ptpi_plan_fk')
                ->references('id')
                ->on('pharmacy_treatment_plans')
                ->onDelete('cascade');
            $table->foreignUuid('prescription_item_id');
            $table->foreign('prescription_item_id', 'ptpi_rx_item_fk')
                ->references('id')
                ->on('prescription_items')
                ->onDelete('cascade');
            $table->foreignUuid('inventory_item_id');
            $table->foreign('inventory_item_id', 'ptpi_item_fk')
                ->references('id')
                ->on('inventory_items')
                ->restrictOnDelete();
            $table->decimal('authorized_total_quantity', 14, 3);
            $table->decimal('quantity_per_cycle', 14, 3);
            $table->unsignedInteger('total_cycles');
            $table->unsignedInteger('completed_cycles')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(
                ['pharmacy_treatment_plan_id', 'prescription_item_id'],
                'pharmacy_treatment_plan_items_lookup',
            );
        });
        }

        if (! Schema::hasTable('currencies')) {
        Schema::create('pharmacy_treatment_plan_cycles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pharmacy_treatment_plan_id');
            $table->foreign('pharmacy_treatment_plan_id', 'ptpc_plan_fk')
                ->references('id')
                ->on('pharmacy_treatment_plans')
                ->onDelete('cascade');
            $table->unsignedInteger('cycle_number');
            $table->date('scheduled_for');
            $table->enum('status', array_column(PharmacyTreatmentPlanCycleStatus::cases(), 'value'))
                ->default(PharmacyTreatmentPlanCycleStatus::PENDING->value)
                ->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('dispensing_record_id')->nullable();
            $table->foreign('dispensing_record_id', 'ptpc_dispense_fk')
                ->references('id')
                ->on('dispensing_records')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['pharmacy_treatment_plan_id', 'cycle_number'],
                'pharmacy_treatment_plan_cycles_plan_cycle_unique',
            );
            $table->index(
                ['pharmacy_treatment_plan_id', 'status', 'scheduled_for'],
                'pharmacy_treatment_plan_cycles_schedule_lookup',
            );
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_treatment_plan_cycles');
        Schema::dropIfExists('pharmacy_treatment_plan_items');
        Schema::dropIfExists('pharmacy_treatment_plans');
    }
};
