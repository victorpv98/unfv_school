<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentService
{
    public function assign(Teacher $teacher, Course $course, int $academicYearId, int $levelId, int $gradeId, string $section): void
    {
        AcademicYear::findOrFail($academicYearId);

        DB::table('teacher_assignments')->updateOrInsert(
            [
                'academic_year_id' => $academicYearId,
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'level_id' => $levelId,
                'grade_id' => $gradeId,
                'section' => $section,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
