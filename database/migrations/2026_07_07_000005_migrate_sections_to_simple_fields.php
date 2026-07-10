<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sections = Schema::hasTable('sections')
            ? DB::table('sections')->pluck('name', 'id')
            : collect();

        if (Schema::hasTable('enrollments') && ! Schema::hasColumn('enrollments', 'section')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('section', 1)->nullable()->after('section_id');
            });
        }

        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'section_id') && Schema::hasColumn('enrollments', 'section')) {
            DB::table('enrollments')
                ->select(['id', 'section_id'])
                ->orderBy('id')
                ->get()
                ->each(function (object $enrollment) use ($sections): void {
                    DB::table('enrollments')
                        ->where('id', $enrollment->id)
                        ->update(['section' => $sections[$enrollment->section_id] ?? 'A']);
                });

            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('section_id');
            });
        }

        if (Schema::hasTable('course_teacher') && ! Schema::hasTable('teacher_assignments')) {
            Schema::rename('course_teacher', 'teacher_assignments');
        }

        if (Schema::hasTable('teacher_assignments')) {
            Schema::table('teacher_assignments', function (Blueprint $table) {
                if (! Schema::hasColumn('teacher_assignments', 'level_id')) {
                    $table->foreignId('level_id')->nullable()->after('teacher_id')->constrained()->nullOnDelete();
                }

                if (! Schema::hasColumn('teacher_assignments', 'section')) {
                    $table->string('section', 1)->nullable()->after('grade_id');
                }
            });

            $assignmentColumns = Schema::hasColumn('teacher_assignments', 'section_id')
                ? ['id', 'grade_id', 'section_id']
                : ['id', 'grade_id'];

            DB::table('teacher_assignments')
                ->select($assignmentColumns)
                ->orderBy('id')
                ->get()
                ->each(function (object $assignment) use ($sections): void {
                    $levelId = DB::table('grades')->where('id', $assignment->grade_id)->value('level_id');
                    $updates = ['level_id' => $levelId];

                    if (property_exists($assignment, 'section_id')) {
                        $updates['section'] = $sections[$assignment->section_id] ?? 'A';
                    }

                    DB::table('teacher_assignments')
                        ->where('id', $assignment->id)
                        ->update($updates);
                });

            if (Schema::hasColumn('teacher_assignments', 'section_id')) {
                Schema::table('teacher_assignments', function (Blueprint $table) {
                    $table->dropColumn('section_id');
                });
            }
        }

        Schema::dropIfExists('sections');
    }

    public function down(): void
    {
        // This migration intentionally does not restore the old sections table.
    }
};
