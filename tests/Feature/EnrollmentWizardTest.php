<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_enrollment_with_guardian_and_payments(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.test')->firstOrFail();

        $this->actingAs($admin)
            ->post('/matriculas/nueva', [
                'student_code' => 'ALU-999',
                'student_first_names' => 'Pedro',
                'student_last_names' => 'Torres Vega',
                'student_dni' => '79999999',
                'student_birth_date' => '2017-05-01',
                'student_gender' => 'Masculino',
                'student_address' => 'Av. Prueba 100',
                'guardian_first_names' => 'Ana',
                'guardian_last_names' => 'Vega',
                'guardian_dni' => '49999999',
                'guardian_phone' => '999999999',
                'guardian_email' => 'ana.vega@example.com',
                'relationship' => 'Madre',
                'is_primary' => '1',
                'academic_year' => 2026,
                'level_id' => 2,
                'grade_id' => 4,
                'section_name' => 'A',
                'enrolled_at' => '2026-03-01',
                'enrollment_status' => 'matriculado',
            ])
            ->assertRedirect('/matriculas/nueva');

        $student = Student::where('dni', '79999999')->firstOrFail();

        $this->assertDatabaseHas('student_guardian', [
            'student_id' => $student->id,
            'relationship' => 'Madre',
            'is_primary' => true,
        ]);

        $this->assertSame(1, Enrollment::where('student_id', $student->id)->count());
        $this->assertSame(11, StudentPayment::where('student_id', $student->id)->count());
    }
}
