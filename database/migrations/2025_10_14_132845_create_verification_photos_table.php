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
        Schema::create('verification_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('headcount_verification_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path'); // Storage path to photo file
            $table->string('photo_type')->default('verification'); // verification, id_card, etc.
            $table->text('caption')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('photo_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_photos');
    }
};
