<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->decimal('late_fee_percentage', 5, 2)->default(5);
            $table->boolean('blocks_exam_right')->default(true);
            $table->boolean('auto_generate_notice')->default(true);
            $table->string('notice_title')->default('Aviso de mora pendiente');
            $table->text('notice_message')->nullable();
            $table->string('status', 30)->default('activo');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_fee_settings');
    }
};
