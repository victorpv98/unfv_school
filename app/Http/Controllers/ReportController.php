<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(Auth::user()->hasRole('administrador', 'docente'), 403);
        $teacher = Auth::user()->hasRole('docente')
            ? Teacher::where('user_id', Auth::id())->first()
            : null;

        return view('reports.index', [
            'enrollmentsByStatus' => Enrollment::select('status', DB::raw('count(*) as total'))->groupBy('status')->get(),
            'teachersBySpecialty' => Teacher::select('specialty', DB::raw('count(*) as total'))->groupBy('specialty')->get(),
            'evaluationAverages' => TeacherEvaluation::query()
                ->join('teachers', 'teachers.id', '=', 'teacher_evaluations.teacher_id')
                ->when($teacher, fn ($query) => $query->where('teachers.id', $teacher->id))
                ->selectRaw("teachers.first_names || ' ' || teachers.last_names as teacher_name, avg(average_score) as average")
                ->groupBy('teachers.id', 'teachers.first_names', 'teachers.last_names')
                ->orderByDesc('average')
                ->get(),
        ]);
    }
}
