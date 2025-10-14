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
        Schema::create('discrepancy_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discrepancy_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('resolved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('resolved_at');
            $table->enum('resolution_type', ['corrected', 'verified_valid', 'staff_removed', 'data_updated', 'no_action_required', 'escalated']);
            $table->text('resolution_notes');
            $table->enum('outcome', ['resolved', 'partially_resolved', 'unresolved'])->default('resolved');
            $table->timestamps();
            $table->softDeletes();

            $table->index('resolution_type');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancy_resolutions');
    }
};
