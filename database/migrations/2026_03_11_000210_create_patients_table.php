<?php

declare(strict_types=1);

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
        if (!Schema::hasTable('patients')) {
            Schema::create('patients', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('patient_number', 50)->comment('Hospital MRN');
                $table->string('first_name', 100);
                $table->string('last_name', 100);
                $table->string('middle_name', 100)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->integer('age')->nullable();
                $table->enum('age_units', ['year', 'month', 'day'])->nullable();
                $table->string('gender', 10);
                $table->string('email', 255)->nullable()->unique();
                $table->string('phone_number', 20);
                $table->string('alternative_phone', 20)->nullable();
                $table->string('next_of_kin_name', 100)->nullable();
                $table->string('next_of_kin_phone', 20)->nullable();
                $table->string('next_of_kin_relationship', 50)->nullable();
                $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
                $table->string('marital_status', 50)->nullable();
                $table->string('occupation', 100)->nullable();
                $table->string('religion', 50)->nullable();
                $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->string('blood_group', 10)->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['last_name', 'first_name']);
                $table->unique(['tenant_id', 'patient_number']);
                $table->index('patient_number');
                $table->index('phone_number');
                $table->index('date_of_birth');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
