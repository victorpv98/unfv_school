<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Grade;
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
        $assignments = DB::table('teacher_assignments')
            ->join('academic_years', 'academic_years.id', '=', 'teacher_assignments.academic_year_id')
            ->join('teachers', 'teachers.id', '=', 'teacher_assignments.teacher_id')
            ->join('courses', 'courses.id', '=', 'teacher_assignments.course_id')
            ->join('grades', 'grades.id', '=', 'teacher_assignments.grade_id')
            ->join('levels', 'levels.id', '=', 'grades.level_id')
            ->selectRaw("teacher_assignments.id, academic_years.year, teachers.first_names || ' ' || teachers.last_names as teacher_name, courses.name as course_name, levels.name as level_name, grades.name as grade_name, teacher_assignments.section")
            ->orderByDesc('academic_years.year')
            ->orderBy('teacher_name')
            ->get();

        return view('teachers.assignments', [
            'assignments' => $assignments,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'teachers' => Teacher::where('status', 'activo')->orderBy('last_names')->get(),
            'courses' => Course::where('status', 'activo')->orderBy('name')->get(),
            'grades' => Grade::with('level')->orderBy('id')->get(),
            'sections' => ['A', 'B', 'C'],
        ]);
    }

    public function store(Request $request, TeacherAssignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'section' => ['required', 'in:A,B,C'],
        ]);
        $grade = Grade::findOrFail($data['grade_id']);

        $service->assign(
            Teacher::findOrFail($data['teacher_id']),
            Course::findOrFail($data['course_id']),
            (int) $data['academic_year_id'],
            (int) $grade->level_id,
            (int) $data['grade_id'],
            $data['section']
        );

        return back()->with('status', 'Asignación docente registrada correctamente.');
    }
}
