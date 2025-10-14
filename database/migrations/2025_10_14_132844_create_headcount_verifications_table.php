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
        Schema::create('headcount_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('headcount_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('verified_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('verified_at');
            $table->enum('verification_status', ['present', 'absent', 'on_leave', 'ghost'])->default('present');
            $table->string('location')->nullable(); // GPS location or office name
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['headcount_session_id', 'staff_id']);
            $table->index('verification_status');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('headcount_verifications');
    }
};
