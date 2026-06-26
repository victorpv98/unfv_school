<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(
        private readonly PaymentGenerationService $paymentGenerationService,
        private readonly UserAccessService $userAccessService,
    ) {}

    public function createFromWizard(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $student = $this->resolveStudent($data);
            $guardian = $this->resolveGuardian($data);
            $this->syncGuardian($student, $guardian, $data);
            $enrollment = $this->createEnrollment($student, $data);
            $paymentsCreated = $this->paymentGenerationService->generateForEnrollment($enrollment);

            return compact('student', 'guardian', 'enrollment', 'paymentsCreated');
        });
    }

    private function resolveStudent(array $data): Student
    {
        $student = Student::query()
            ->when($data['student_id'] ?? null, fn ($query) => $query->whereKey($data['student_id']))
            ->when(empty($data['student_id']) && ! empty($data['student_dni']), fn ($query) => $query->where('dni', $data['student_dni']))
            ->when(empty($data['student_id']) && empty($data['student_dni']) && ! empty($data['student_code']), fn ($query) => $query->where('code', $data['student_code']))
            ->first();

        $studentAccess = $this->userAccessService->createOptionalAccess(
            [
                'create_user' => $data['create_student_user'] ?? false,
                'user_email' => $data['student_user_email'] ?? null,
                'user_password' => $data['student_user_password'] ?? null,
            ],
            'alumno',
            trim(($data['student_first_names'] ?? '').' '.($data['student_last_names'] ?? ''))
        );

        $payload = [
            'user_id' => $student?->user_id ?? $studentAccess?->id,
            'code' => $data['student_code'],
            'first_names' => $data['student_first_names'],
            'last_names' => $data['student_last_names'],
            'dni' => $data['student_dni'],
            'birth_date' => $data['student_birth_date'] ?? null,
            'gender' => $data['student_gender'] ?? null,
            'address' => $data['student_address'] ?? null,
            'status' => 'activo',
            'updated_by' => Auth::id(),
        ];

        if ($student) {
            $student->update($payload);
            return $student;
        }

        $payload['created_by'] = Auth::id();

        return Student::create($payload);
    }

    private function resolveGuardian(array $data): Guardian
    {
        $guardian = Guardian::query()
            ->when($data['guardian_id'] ?? null, fn ($query) => $query->whereKey($data['guardian_id']))
            ->when(empty($data['guardian_id']) && ! empty($data['guardian_dni']), fn ($query) => $query->where('dni', $data['guardian_dni']))
            ->when(empty($data['guardian_id']) && empty($data['guardian_dni']) && ! empty($data['guardian_phone']), fn ($query) => $query->where('phone', $data['guardian_phone']))
            ->first();

        $guardianAccess = $this->userAccessService->createOptionalAccess(
            [
                'create_user' => $data['create_guardian_user'] ?? false,
                'user_email' => $data['guardian_user_email'] ?? null,
                'user_password' => $data['guardian_user_password'] ?? null,
            ],
            'apoderado',
            trim($data['guardian_first_names'].' '.$data['guardian_last_names'])
        );

        $payload = [
            'user_id' => $guardian?->user_id ?? $guardianAccess?->id,
            'first_names' => $data['guardian_first_names'],
            'last_names' => $data['guardian_last_names'],
            'dni' => $data['guardian_dni'],
            'phone' => $data['guardian_phone'] ?? null,
            'email' => $data['guardian_email'] ?? null,
            'address' => $data['guardian_address'] ?? null,
            'relationship' => $data['relationship'],
            'status' => 'activo',
            'updated_by' => Auth::id(),
        ];

        if ($guardian) {
            $guardian->update($payload);
            return $guardian;
        }

        $payload['created_by'] = Auth::id();

        return Guardian::create($payload);
    }

    private function syncGuardian(Student $student, Guardian $guardian, array $data): void
    {
        if (! empty($data['is_primary'])) {
            DB::table('student_guardian')->where('student_id', $student->id)->update(['is_primary' => false]);
        }

        $student->guardians()->syncWithoutDetaching([
            $guardian->id => [
                'relationship' => $data['relationship'],
                'is_primary' => (bool) ($data['is_primary'] ?? false),
                'status' => 'activo',
                'created_by' => Auth::id(),
            ],
        ]);
    }

    private function createEnrollment(Student $student, array $data): Enrollment
    {
        $academicYear = AcademicYear::firstOrCreate(
            ['year' => (int) $data['academic_year']],
            ['status' => 'activo']
        );

        if (Enrollment::where('student_id', $student->id)->where('academic_year_id', $academicYear->id)->exists()) {
            throw ValidationException::withMessages([
                'academic_year' => 'El alumno ya tiene una matrícula registrada para ese año académico.',
            ]);
        }

        $grade = Grade::findOrFail($data['grade_id']);
        $section = Section::firstOrCreate([
            'grade_id' => $grade->id,
            'name' => $data['section_name'],
        ], ['status' => 'activo']);

        return Enrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'level_id' => $data['level_id'],
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrolled_at' => $data['enrolled_at'],
            'status' => $data['enrollment_status'] ?? 'matriculado',
            'observations' => $data['observations'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
