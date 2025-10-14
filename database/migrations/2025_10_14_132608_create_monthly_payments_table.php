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
        Schema::create('monthly_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('payment_month'); // e.g., 2025-10-01 for October 2025
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('deductions_total', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->enum('payment_status', ['pending', 'approved', 'processing', 'paid', 'failed'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['staff_id', 'payment_month']);
            $table->index('payment_month');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_payments');
    }
};
