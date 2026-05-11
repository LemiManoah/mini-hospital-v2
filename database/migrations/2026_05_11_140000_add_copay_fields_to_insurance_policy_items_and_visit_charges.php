<?php

declare(strict_types=1);

use App\Enums\InsuranceCopayType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_policy_items')) {
            Schema::table('insurance_policy_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('insurance_policy_items', 'copay_type')) {
                    $table->enum('copay_type', array_column(InsuranceCopayType::cases(), 'value'))->default(InsuranceCopayType::NONE->value);
                }

                if (! Schema::hasColumn('insurance_policy_items', 'copay_value')) {
                    $table->decimal('copay_value', 14, 2)->default(0);
                }
            });
        }

        if (Schema::hasTable('visit_charges')) {
            Schema::table('visit_charges', function (Blueprint $table): void {
                if (! Schema::hasColumn('visit_charges', 'copay_amount')) {
                    $table->decimal('copay_amount', 15, 2)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visit_charges')) {
            Schema::table('visit_charges', function (Blueprint $table): void {
                if (Schema::hasColumn('visit_charges', 'copay_amount')) {
                    $table->dropColumn('copay_amount');
                }
            });
        }

        if (Schema::hasTable('insurance_policy_items')) {
            Schema::table('insurance_policy_items', function (Blueprint $table): void {
                if (Schema::hasColumn('insurance_policy_items', 'copay_value')) {
                    $table->dropColumn('copay_value');
                }

                if (Schema::hasColumn('insurance_policy_items', 'copay_type')) {
                    $table->dropColumn('copay_type');
                }
            });
        }
    }
};
