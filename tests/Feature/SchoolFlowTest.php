<?php

namespace Tests\Feature;

use App\Models\EvaluationCriterion;
use App\Models\PaymentConcept;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_users_can_login_with_real_roles(): void
    {
        $this->seed();

        foreach ([
            'admin@school.test',
            'secretaria@school.test',
            'docente@school.test',
            'alumno@school.test',
            'apoderado@school.test',
        ] as $email) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ])->assertRedirect('/dashboard');

            $this->post('/logout')->assertRedirect('/');
        }
    }

    public function test_unauthorized_roles_cannot_open_admin_resources(): void
    {
        $this->seed();

        $studentUser = User::where('email', 'alumno@school.test')->firstOrFail();

        $this->actingAs($studentUser)
            ->get('/admin/users')
            ->assertRedirect('/dashboard');
    }

    public function test_secretary_can_register_a_payment(): void
    {
        $this->seed();

        $secretary = User::where('email', 'secretaria@school.test')->firstOrFail();
        $payment = StudentPayment::with(['student', 'paymentConcept'])->firstOrFail();

        $this->actingAs($secretary)
            ->put("/admin/student-payments/{$payment->id}", [
                'student_id' => $payment->student_id,
                'payment_concept_id' => $payment->payment_concept_id,
                'amount' => $payment->amount,
                'amount_paid' => $payment->amount,
                'status' => 'pagado',
                'due_date' => optional($payment->due_date)->toDateString(),
                'paid_at' => '2026-03-02',
                'payment_method' => 'efectivo',
                'receipt_number' => 'R-001',
            ])
            ->assertRedirect('/admin/student-payments');

        $this->assertDatabaseHas('student_payments', [
            'id' => $payment->id,
            'status' => 'pagado',
            'payment_method' => 'efectivo',
            'receipt_number' => 'R-001',
        ]);
    }

    public function test_enrollment_rejects_invalid_section(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.test')->firstOrFail();

        $this->actingAs($admin)
            ->post('/matriculas/nueva', [
                'student_code' => 'ALU-998',
                'student_first_names' => 'Mario',
                'student_last_names' => 'Prueba',
                'student_dni' => '79999998',
                'guardian_first_names' => 'Rosa',
                'guardian_last_names' => 'Prueba',
                'guardian_dni' => '49999998',
                'relationship' => 'Madre',
                'academic_year' => 2026,
                'level_id' => 2,
                'grade_id' => 4,
                'section_name' => 'D',
                'enrolled_at' => '2026-03-01',
                'enrollment_status' => 'matriculado',
            ])
            ->assertSessionHasErrors('section_name');
    }

    public function test_student_can_evaluate_teacher_only_once_per_period(): void
    {
        $this->seed();

        $studentUser = User::where('email', 'alumno@school.test')->firstOrFail();
        $teacher = Teacher::firstOrFail();
        $criteria = EvaluationCriterion::where('evaluator_type', 'alumno')->pluck('id');

        $payload = [
            'scores' => $criteria->mapWithKeys(fn (int $id) => [$id => 5])->all(),
            'comment' => 'Buen docente.',
        ];

        $this->actingAs($studentUser)
            ->post("/evaluaciones/{$teacher->id}", $payload)
            ->assertRedirect('/evaluaciones');

        $this->assertSame(1, TeacherEvaluation::where('teacher_id', $teacher->id)->where('user_id', $studentUser->id)->count());

        $this->actingAs($studentUser)
            ->post("/evaluaciones/{$teacher->id}", $payload)
            ->assertForbidden();
    }

    public function test_guardian_can_evaluate_teacher_of_associated_child(): void
    {
        $this->seed();

        $guardianUser = User::where('email', 'apoderado@school.test')->firstOrFail();
        $teacher = Teacher::firstOrFail();
        $criteria = EvaluationCriterion::where('evaluator_type', 'apoderado')->pluck('id');

        $this->actingAs($guardianUser)
            ->post("/evaluaciones/{$teacher->id}", [
                'scores' => $criteria->mapWithKeys(fn (int $id) => [$id => 4])->all(),
                'comment' => 'Comunicación adecuada.',
            ])
            ->assertRedirect('/evaluaciones');

        $this->assertSame(1, TeacherEvaluation::where('teacher_id', $teacher->id)->where('user_id', $guardianUser->id)->count());
    }

    public function test_enrollment_generation_uses_simple_section_field(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.test')->firstOrFail();
        $conceptCount = PaymentConcept::where('academic_year_id', 1)->count();

        $this->actingAs($admin)
            ->post('/matriculas/nueva', [
                'student_code' => 'ALU-997',
                'student_first_names' => 'Lucero',
                'student_last_names' => 'Campos',
                'student_dni' => '79999997',
                'guardian_first_names' => 'Elena',
                'guardian_last_names' => 'Campos',
                'guardian_dni' => '49999997',
                'relationship' => 'Madre',
                'academic_year' => 2026,
                'level_id' => 2,
                'grade_id' => 4,
                'section_name' => 'B',
                'enrolled_at' => '2026-03-01',
                'enrollment_status' => 'matriculado',
            ])
            ->assertRedirect('/matriculas/nueva');

        $student = Student::where('dni', '79999997')->firstOrFail();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'section' => 'B',
        ]);
        $this->assertSame($conceptCount, StudentPayment::where('student_id', $student->id)->count());
    }
}
