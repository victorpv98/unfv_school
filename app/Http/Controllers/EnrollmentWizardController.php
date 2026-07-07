<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Level;
use App\Models\Student;
use App\Models\Guardian;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentWizardController extends Controller
{
    public function create(): View
    {
        return view('enrollments.wizard', [
            'levels' => Level::orderBy('id')->get(),
            'grades' => Grade::with('level')->orderBy('id')->get(),
            'students' => Student::orderBy('last_names')->get(),
            'guardians' => Guardian::orderBy('last_names')->get(),
            'summary' => session('enrollment_summary'),
        ]);
    }

    public function store(Request $request, EnrollmentService $enrollmentService): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'exists:students,id'],
            'student_code' => ['required', 'max:255'],
            'student_first_names' => ['required', 'max:255'],
            'student_last_names' => ['required', 'max:255'],
            'student_dni' => ['required', 'max:20'],
            'student_birth_date' => ['nullable', 'date'],
            'student_gender' => ['nullable', 'in:Masculino,Femenino'],
            'student_address' => ['nullable', 'max:255'],
            'create_student_user' => ['nullable', 'boolean'],
            'student_user_email' => ['nullable', 'email', 'max:255'],
            'student_user_password' => ['nullable', 'min:6'],

            'guardian_id' => ['nullable', 'exists:guardians,id'],
            'guardian_first_names' => ['required', 'max:255'],
            'guardian_last_names' => ['required', 'max:255'],
            'guardian_dni' => ['required', 'max:20'],
            'guardian_phone' => ['nullable', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_address' => ['nullable', 'max:255'],
            'relationship' => ['required', 'in:Madre,Padre,Apoderado'],
            'is_primary' => ['nullable', 'boolean'],
            'create_guardian_user' => ['nullable', 'boolean'],
            'guardian_user_email' => ['nullable', 'email', 'max:255'],
            'guardian_user_password' => ['nullable', 'min:6'],

            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'level_id' => ['required', 'exists:levels,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'section_name' => ['required', 'in:A,B,C'],
            'enrolled_at' => ['required', 'date'],
            'enrollment_status' => ['required', 'in:pendiente,matriculado,observado,anulado,retirado'],
            'observations' => ['nullable', 'string'],
        ]);

        $result = $enrollmentService->createFromWizard($data);
        $enrollment = $result['enrollment']->load(['academicYear', 'level', 'grade']);

        return redirect()
            ->route('enrollments.wizard.create')
            ->with('status', 'Matrícula creada correctamente.')
            ->with('enrollment_summary', [
                'student' => $result['student']->first_names.' '.$result['student']->last_names,
                'guardian' => $result['guardian']->first_names.' '.$result['guardian']->last_names,
                'academic' => $enrollment->academicYear->year.' - '.$enrollment->level->name.' - '.$enrollment->grade->name.' '.$enrollment->section,
                'payments_created' => $result['paymentsCreated'],
            ]);
    }
}
