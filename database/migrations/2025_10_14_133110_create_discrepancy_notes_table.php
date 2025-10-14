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
        Schema::create('discrepancy_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discrepancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('note_content');
            $table->boolean('is_internal')->default(false); // Internal notes vs client-facing
            $table->timestamps();
            $table->softDeletes();

            $table->index('discrepancy_id');
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancy_notes');
    }
};
