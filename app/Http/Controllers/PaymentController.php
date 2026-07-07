<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\PaymentConcept;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $students = $user->hasRole('administrador', 'secretaria')
            ? Student::query()->with(['enrollments.academicYear', 'payments.paymentConcept'])->orderBy('last_names')->get()
            : Guardian::where('user_id', $user->id)->first()?->students()
                ->with(['enrollments.academicYear', 'payments.paymentConcept'])
                ->orderBy('last_names')
                ->get() ?? collect();

        $academicYearIds = $students
            ->flatMap(fn (Student $student) => $student->enrollments->pluck('academic_year_id'))
            ->filter()
            ->unique()
            ->values();

        $concepts = PaymentConcept::query()
            ->with('academicYear')
            ->whereIn('academic_year_id', $academicYearIds)
            ->where('status', 'activo')
            ->orderBy('academic_year_id')
            ->orderByRaw("case when type = 'matricula' then 0 else 1 end")
            ->orderBy('month')
            ->get();

        return view('payments.index', [
            'students' => $students,
            'concepts' => $concepts,
        ]);
    }
}
