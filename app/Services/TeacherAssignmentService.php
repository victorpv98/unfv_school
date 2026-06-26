<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentService
{
    public function assign(Teacher $teacher, Course $course, int $academicYearId, int $gradeId, int $sectionId): void
    {
        AcademicYear::findOrFail($academicYearId);

        DB::table('course_teacher')->updateOrInsert(
            [
                'academic_year_id' => $academicYearId,
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'grade_id' => $gradeId,
                'section_id' => $sectionId,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
