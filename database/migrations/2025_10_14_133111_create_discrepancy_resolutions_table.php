<?php

use App\Enums\ResolutionOutcome;
use App\Enums\ResolutionType;
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
            $table->enum('resolution_type', ResolutionType::cases());
            $table->text('resolution_notes');
            $table->enum('outcome', ResolutionOutcome::cases())->default(ResolutionOutcome::Resolved);
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
