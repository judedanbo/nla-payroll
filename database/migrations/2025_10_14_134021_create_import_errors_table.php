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
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_history_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('field_name')->nullable();
            $table->text('error_message');
            $table->json('row_data'); // Store the full row data for reference
            $table->timestamps();

            $table->index('import_history_id');
            $table->index('field_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
