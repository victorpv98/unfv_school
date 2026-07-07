<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        $guardian = Guardian::where('user_id', $user->id)->first();
        $student = Student::where('user_id', $user->id)->first();

        return view('dashboard.index', [
            'role' => $user->role,
            'cards' => [
                'Usuarios' => User::count(),
                'Alumnos' => Student::count(),
                'Apoderados' => Guardian::count(),
                'Docentes' => Teacher::count(),
                'Matrículas activas' => Enrollment::where('status', 'matriculado')->count(),
                'Pagos pendientes' => StudentPayment::whereIn('status', ['pendiente', 'parcial', 'vencido'])->count(),
                'Pagos en mora' => StudentPayment::where('late_fee_amount', '>', 0)->count(),
                'Sin derecho a examen' => StudentPayment::where('exam_blocked', true)->distinct('student_id')->count('student_id'),
                'Comunicados publicados' => Announcement::where('status', 'published')->count(),
                'Comunicados por leer' => AnnouncementRecipient::whereNull('read_at')->count(),
                'Evaluaciones' => TeacherEvaluation::count(),
            ],
            'teacherStats' => $teacher ? [
                'average' => (float) TeacherEvaluation::where('teacher_id', $teacher->id)->avg('average_score'),
                'count' => TeacherEvaluation::where('teacher_id', $teacher->id)->count(),
                'comments' => TeacherEvaluation::where('teacher_id', $teacher->id)->whereNotNull('comment')->latest('id')->limit(5)->get(),
            ] : null,
            'guardianStats' => $guardian ? [
                'children' => $guardian->students()->count(),
                'pending' => StudentPayment::whereIn('student_id', $guardian->students()->pluck('students.id'))->whereIn('status', ['pendiente', 'parcial', 'vencido'])->count(),
                'paid' => StudentPayment::whereIn('student_id', $guardian->students()->pluck('students.id'))->where('status', 'pagado')->count(),
            ] : null,
            'studentStats' => $student ? [
                'completed' => TeacherEvaluation::where('student_id', $student->id)->count(),
            ] : null,
            'bestTeachers' => Teacher::withAvg('evaluations', 'average_score')
                ->orderByDesc('evaluations_avg_average_score')
                ->limit(5)
                ->get(),
        ]);
    }
}
