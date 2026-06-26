<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_concept_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status', 30)->default('pendiente');
            $table->date('paid_at')->nullable();
            $table->string('receipt_number')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'payment_concept_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
