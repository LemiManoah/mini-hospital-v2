<?php

declare(strict_types=1);

use App\Enums\LabSpecimenStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_specimens')) {
            Schema::create('lab_specimens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('lab_request_item_id')->constrained('lab_request_items')->onDelete('cascade');
                $table->string('accession_number', 40)->unique();
                $table->foreignUuid('specimen_type_id')->constrained('specimen_types')->restrictOnDelete();
                $table->string('specimen_type_name', 100);
                $table->enum('status', array_column(LabSpecimenStatus::cases(), 'value'))->default(LabSpecimenStatus::COLLECTED->value)->index();
                $table->foreignUuid('collected_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('collected_at')->nullable()->index();
                $table->boolean('outside_sample')->default(false);
                $table->string('outside_sample_origin', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('lab_request_item_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_specimens');
    }
};
