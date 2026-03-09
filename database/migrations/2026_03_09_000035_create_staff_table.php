<?php

declare(strict_types=1);

use App\Enums\StaffType;
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
        Schema::create('staff', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('employee_number', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('email')->index();
            $table->string('phone', 20)->nullable();
            $table->foreignUuid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('staff_position_id')->nullable()->constrained('staff_positions')->nullOnDelete();
            $table->enum('type', array_column(StaffType::cases(), 'value'));
            $table->string('license_number', 100)->nullable();
            $table->string('specialty', 100)->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Audit fields
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_number']);
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'license_number']);
            $table->index('is_active');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
