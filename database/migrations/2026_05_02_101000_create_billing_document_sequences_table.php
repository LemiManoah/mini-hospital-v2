<?php

declare(strict_types=1);

use App\Enums\BillingDocumentType;
use App\Enums\BillingSequenceResetPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_document_sequences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('facility_branch_id')->nullable()->constrained('facility_branches')->nullOnDelete();
            $table->enum('document_type', array_column(BillingDocumentType::cases(), 'value'));
            $table->string('prefix', 20);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->enum('reset_period', array_column(BillingSequenceResetPeriod::cases(), 'value'))
                ->default(BillingSequenceResetPeriod::Yearly->value);
            $table->string('current_period_key', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'facility_branch_id', 'document_type'], 'billing_document_sequences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_sequences');
    }
};
