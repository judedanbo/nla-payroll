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
        Schema::create('payment_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_payment_id')->constrained()->cascadeOnDelete();
            $table->enum('element_type', ['basic_salary', 'allowance', 'deduction']);
            $table->string('element_name'); // e.g., 'Housing Allowance', 'Transport', 'Tax', 'SSNIT'
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['monthly_payment_id', 'element_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_elements');
    }
};
