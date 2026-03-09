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
        Schema::create('staff_branches', function (Blueprint $table): void {
            $table->foreignUuid('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('facility_branches')->onDelete('cascade');
            $table->boolean('is_primary_location')->default(false);

            $table->primary(['staff_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_branches');
    }
};
