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
        Schema::create('staff_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            // Old assignment
            $table->foreignId('old_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('old_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('old_station_id')->nullable()->constrained('stations')->nullOnDelete();

            // New assignment
            $table->foreignId('new_department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('new_unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('new_station_id')->constrained('stations')->cascadeOnDelete();

            $table->timestamp('changed_at');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('staff_id');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_assignment_histories');
    }
};
