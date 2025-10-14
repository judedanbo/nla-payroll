<?php

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
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
            $table->enum('discrepancy_type', DiscrepancyType::cases());
            $table->enum('severity', Severity::cases())->default(Severity::Medium);
            $table->text('description');
            $table->enum('status', DiscrepancyStatus::cases())->default(DiscrepancyStatus::Open);
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
