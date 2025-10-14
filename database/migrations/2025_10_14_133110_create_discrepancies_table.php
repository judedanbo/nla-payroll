<?php

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
        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('discrepancy_type', ['ghost_employee', 'duplicate_bank_account', 'station_mismatch', 'salary_anomaly', 'missing_data', 'unregistered_personnel', 'other']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->enum('status', ['open', 'under_review', 'resolved', 'dismissed'])->default('open');
            $table->foreignId('detected_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('detected_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'severity']);
            $table->index('discrepancy_type');
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
    }
};
