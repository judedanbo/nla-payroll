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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();

            // Bio Data
            $table->string('staff_number')->unique();
            $table->string('full_name');

            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('marital_status')->nullable();

            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Employment Details
            $table->date('date_of_hire')->nullable();
            $table->date('date_of_termination')->nullable();
            $table->enum('employment_status', ['active', 'on_leave', 'suspended', 'terminated', 'retired'])->default('active');
            $table->enum('employment_type', ['permanent', 'contract', 'temporary', 'intern'])->default('permanent');
            $table->decimal('current_salary', 12, 2)->nullable();

            // Verification Status
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_ghost')->default(false)->comment('Flagged as potential ghost employee');
            $table->text('ghost_reason')->nullable();

            // System Fields
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('staff_number');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('unit_id');
            $table->index('job_title_id');
            $table->index('station_id');
            $table->index('employment_status');
            $table->index('is_active');
            $table->index('is_verified');
            $table->index('is_ghost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
