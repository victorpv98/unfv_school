<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('student_payments', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->default(0)->after('amount');
            }

            if (! Schema::hasColumn('student_payments', 'late_fee_amount')) {
                $table->decimal('late_fee_amount', 10, 2)->default(0)->after('original_amount');
            }

            if (! Schema::hasColumn('student_payments', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('late_fee_amount');
            }

            if (! Schema::hasColumn('student_payments', 'late_fee_applied_at')) {
                $table->timestamp('late_fee_applied_at')->nullable()->after('due_date');
            }

            if (! Schema::hasColumn('student_payments', 'exam_blocked')) {
                $table->boolean('exam_blocked')->default(false)->after('late_fee_applied_at');
            }

            if (! Schema::hasColumn('student_payments', 'exam_blocked_at')) {
                $table->timestamp('exam_blocked_at')->nullable()->after('exam_blocked');
            }

            if (! Schema::hasColumn('student_payments', 'exam_unblocked_at')) {
                $table->timestamp('exam_unblocked_at')->nullable()->after('exam_blocked_at');
            }

            if (! Schema::hasColumn('student_payments', 'notice_generated_at')) {
                $table->timestamp('notice_generated_at')->nullable()->after('exam_unblocked_at');
            }

            $table->index(['status', 'due_date']);
            $table->index(['student_id', 'exam_blocked']);
        });

        DB::table('student_payments')->where('original_amount', 0)->update([
            'original_amount' => DB::raw('amount'),
            'total_amount' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            foreach ([
                'original_amount',
                'late_fee_amount',
                'total_amount',
                'late_fee_applied_at',
                'exam_blocked',
                'exam_blocked_at',
                'exam_unblocked_at',
                'notice_generated_at',
            ] as $column) {
                if (Schema::hasColumn('student_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
