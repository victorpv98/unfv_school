<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard.index', [
            'cards' => [
                'Usuarios' => User::count(),
                'Alumnos' => Student::count(),
                'Apoderados' => Guardian::count(),
                'Profesores' => Teacher::count(),
                'Matrículas' => Enrollment::count(),
                'Evaluaciones' => TeacherEvaluation::count(),
            ],
            'bestTeachers' => Teacher::withAvg('evaluations', 'average_score')
                ->orderByDesc('evaluations_avg_average_score')
                ->limit(5)
                ->get(),
        ]);
    }
}
