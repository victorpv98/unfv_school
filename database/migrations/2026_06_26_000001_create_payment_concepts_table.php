<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('name');
            $table->unsignedTinyInteger('month')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('activo');
            $table->timestamps();
            $table->unique(['academic_year_id', 'type', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_concepts');
    }
};
