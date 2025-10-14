<?php

use App\Enums\VerificationNoteType;
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
        Schema::create('verification_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('headcount_verification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('note_content');
            $table->enum('note_type', VerificationNoteType::cases())->default(VerificationNoteType::General);
            $table->timestamps();
            $table->softDeletes();

            $table->index('note_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_notes');
    }
};
