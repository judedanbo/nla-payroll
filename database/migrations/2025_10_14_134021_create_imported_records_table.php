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
        Schema::create('imported_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_history_id')->constrained()->cascadeOnDelete();
            $table->morphs('recordable'); // Polymorphic relation to Staff, BankDetail, etc. (automatically indexed)
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->json('original_data'); // Original CSV data
            $table->timestamps();

            $table->index(['import_history_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_records');
    }
};
