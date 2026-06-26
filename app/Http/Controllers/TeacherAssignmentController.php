<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Teacher;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = DB::table('course_teacher')
            ->join('academic_years', 'academic_years.id', '=', 'course_teacher.academic_year_id')
            ->join('teachers', 'teachers.id', '=', 'course_teacher.teacher_id')
            ->join('courses', 'courses.id', '=', 'course_teacher.course_id')
            ->join('grades', 'grades.id', '=', 'course_teacher.grade_id')
            ->join('levels', 'levels.id', '=', 'grades.level_id')
            ->join('sections', 'sections.id', '=', 'course_teacher.section_id')
            ->selectRaw("course_teacher.id, academic_years.year, teachers.first_names || ' ' || teachers.last_names as teacher_name, courses.name as course_name, levels.name as level_name, grades.name as grade_name, sections.name as section_name")
            ->orderByDesc('academic_years.year')
            ->orderBy('teacher_name')
            ->get();

        return view('teachers.assignments', [
            'assignments' => $assignments,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'teachers' => Teacher::where('status', 'activo')->orderBy('last_names')->get(),
            'courses' => Course::where('status', 'activo')->orderBy('name')->get(),
            'grades' => Grade::with('level')->orderBy('id')->get(),
            'sections' => Section::with('grade.level')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, TeacherAssignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $service->assign(
            Teacher::findOrFail($data['teacher_id']),
            Course::findOrFail($data['course_id']),
            (int) $data['academic_year_id'],
            (int) $data['grade_id'],
            (int) $data['section_id']
        );

        return back()->with('status', 'Asignación docente registrada correctamente.');
    }
}
