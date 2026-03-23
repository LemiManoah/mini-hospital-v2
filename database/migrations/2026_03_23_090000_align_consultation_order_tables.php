<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facility_service_orders')) {
            Schema::table('facility_service_orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('facility_service_orders', 'tenant_id')) {
                    $table->uuid('tenant_id')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'facility_branch_id')) {
                    $table->uuid('facility_branch_id')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'consultation_id')) {
                    $table->uuid('consultation_id')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'visit_id')) {
                    $table->uuid('visit_id')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'facility_service_id')) {
                    $table->uuid('facility_service_id')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'ordered_by')) {
                    $table->uuid('ordered_by')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'status')) {
                    $table->string('status', 50)->default('pending')->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'ordered_at')) {
                    $table->timestamp('ordered_at')->nullable();
                }

                if (! Schema::hasColumn('facility_service_orders', 'performed_by')) {
                    $table->uuid('performed_by')->nullable()->index();
                }

                if (! Schema::hasColumn('facility_service_orders', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable();
                }

                if (! Schema::hasColumn('facility_service_orders', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable();
                }
            });

            Schema::table('facility_service_orders', function (Blueprint $table): void {
                if (Schema::hasColumn('facility_service_orders', 'clinical_notes')) {
                    $table->dropColumn('clinical_notes');
                }

                if (Schema::hasColumn('facility_service_orders', 'service_instructions')) {
                    $table->dropColumn('service_instructions');
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
