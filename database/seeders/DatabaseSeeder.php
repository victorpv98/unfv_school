<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationPeriod;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\LateFeeSetting;
use App\Models\Level;
use App\Models\PaymentConcept;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@school.test'],
            ['role' => 'administrador', 'name' => 'Administrador', 'password' => Hash::make('password'), 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'secretaria@school.test'],
            ['role' => 'secretaria', 'name' => 'Secretaría Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $studentUser = User::updateOrCreate(
            ['email' => 'alumno@school.test'],
            ['role' => 'alumno', 'name' => 'Alumno Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $guardianUser = User::updateOrCreate(
            ['email' => 'apoderado@school.test'],
            ['role' => 'apoderado', 'name' => 'Apoderado Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $teacherUser = User::updateOrCreate(
            ['email' => 'docente@school.test'],
            ['role' => 'docente', 'name' => 'Docente Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $year = AcademicYear::firstOrCreate(['year' => 2026], [
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-12-20',
            'status' => 'activo',
        ]);

        $academicStructure = [
            'Inicial' => ['3 años', '4 años', '5 años'],
            'Primaria' => ['1° grado', '2° grado', '3° grado', '4° grado', '5° grado', '6° grado'],
        ];

        $catalog = [];

        foreach ($academicStructure as $levelName => $gradeNames) {
            $level = Level::firstOrCreate(['name' => $levelName], ['status' => 'activo']);
            $catalog[$levelName]['level'] = $level;

            foreach ($gradeNames as $index => $gradeName) {
                $grade = Grade::firstOrCreate(['level_id' => $level->id, 'name' => $gradeName], ['sort_order' => $index + 1, 'status' => 'activo']);
                $catalog[$levelName]['grades'][$gradeName] = $grade;
            }
        }

        $level = $catalog['Primaria']['level'];
        $grade = $catalog['Primaria']['grades']['1° grado'];
        $section = 'A';

        $student = Student::updateOrCreate(['code' => 'ALU-001'], [
            'user_id' => $studentUser->id,
            'first_names' => 'Lucía',
            'last_names' => 'Pérez Ramos',
            'dni' => '70000001',
            'birth_date' => '2018-04-10',
            'gender' => 'Femenino',
            'address' => 'Av. Escolar 123',
            'status' => 'activo',
        ]);

        $guardian = Guardian::updateOrCreate(['dni' => '40000001'], [
            'user_id' => $guardianUser->id,
            'first_names' => 'María',
            'last_names' => 'Ramos',
            'phone' => '999888777',
            'email' => 'maria.ramos@example.com',
            'address' => 'Av. Escolar 123',
            'relationship' => 'Madre',
            'status' => 'activo',
        ]);

        $student->guardians()->syncWithoutDetaching([
            $guardian->id => ['relationship' => $guardian->relationship, 'is_primary' => true],
        ]);
        $student->guardians()->updateExistingPivot($guardian->id, [
            'relationship' => $guardian->relationship,
            'is_primary' => true,
        ]);

        $teacher = Teacher::updateOrCreate(['code' => 'DOC-001'], [
            'user_id' => $teacherUser->id,
            'first_names' => 'Carlos',
            'last_names' => 'Gómez Silva',
            'dni' => '40000002',
            'phone' => '988777666',
            'email' => 'carlos.gomez@example.com',
            'specialty' => 'Matemática',
            'status' => 'activo',
        ]);

        $course = Course::firstOrCreate(['code' => 'MAT-1P'], [
            'level_id' => $level->id,
            'grade_id' => $grade->id,
            'name' => 'Matemática',
            'status' => 'activo',
        ]);

        $course->teachers()->syncWithoutDetaching([$teacher->id => [
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
            'grade_id' => $grade->id,
            'section' => $section,
        ]]);

        $enrollment = $student->enrollments()->firstOrCreate(['academic_year_id' => $year->id], [
            'guardian_id' => $guardian->id,
            'level_id' => $level->id,
            'grade_id' => $grade->id,
            'section' => $section,
            'enrolled_at' => now()->toDateString(),
            'status' => 'matriculado',
        ]);

        PaymentConcept::firstOrCreate([
            'academic_year_id' => $year->id,
            'type' => 'matricula',
            'month' => null,
        ], [
            'name' => 'Matrícula 2026',
            'amount' => 250,
            'due_date' => '2026-03-01',
            'status' => 'activo',
        ]);

        foreach ([
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ] as $month => $monthName) {
            PaymentConcept::firstOrCreate([
                'academic_year_id' => $year->id,
                'type' => 'mensualidad',
                'month' => $month,
            ], [
                'name' => "Mensualidad {$monthName}",
                'amount' => 180,
                'due_date' => sprintf('2026-%02d-05', $month),
                'status' => 'activo',
            ]);
        }

        PaymentConcept::where('academic_year_id', $year->id)
            ->where('status', 'activo')
            ->get()
            ->each(function (PaymentConcept $concept) use ($student, $enrollment): void {
                StudentPayment::firstOrCreate([
                    'student_id' => $student->id,
                    'payment_concept_id' => $concept->id,
                ], [
                    'enrollment_id' => $enrollment->id,
                    'amount' => $concept->amount,
                    'original_amount' => $concept->amount,
                    'late_fee_amount' => 0,
                    'total_amount' => $concept->amount,
                    'amount_paid' => 0,
                    'status' => 'pendiente',
                    'due_date' => $concept->due_date,
                ]);
            });

        LateFeeSetting::firstOrCreate([
            'academic_year_id' => $year->id,
            'status' => 'activo',
        ], [
            'name' => 'Mora 2026',
            'grace_days' => 5,
            'late_fee_percentage' => 5,
            'blocks_exam_right' => true,
            'auto_generate_notice' => true,
            'notice_title' => 'Aviso de mora pendiente',
            'notice_message' => 'Estimado apoderado, se informa que el alumno [NOMBRE_ALUMNO] mantiene una mensualidad vencida correspondiente a [CONCEPTO]. Se ha aplicado una comisión adicional del [PORCENTAJE_MORA]%. Total a pagar: S/ [TOTAL_A_PAGAR].',
        ]);

        $announcement = Announcement::firstOrCreate([
            'title' => 'Bienvenida al año escolar 2026',
            'target_type' => 'all_users',
        ], [
            'message' => 'Bienvenidos al sistema de comunicados de la IEP Sagrado Corazón.',
            'type' => 'general',
            'priority' => 'normal',
            'status' => 'published',
            'publish_at' => now(),
            'created_by' => User::where('email', 'admin@school.test')->value('id'),
        ]);

        User::where('is_active', true)->get()->each(function (User $user) use ($announcement): void {
            AnnouncementRecipient::firstOrCreate([
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ], ['delivered_at' => now()]);
        });

        $period = EvaluationPeriod::firstOrCreate(['name' => 'Evaluación Demo 2026'], [
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => 'activo',
        ]);

        foreach ([
            ['alumno', 'Explicación clara', 'El docente explica de manera entendible'],
            ['alumno', 'Trato respetuoso', 'Trata con respeto a los estudiantes'],
            ['alumno', 'Puntualidad', 'Cumple con el horario de clase'],
            ['apoderado', 'Comunicación con la familia', 'Informa sobre avances o dificultades del estudiante'],
            ['apoderado', 'Responsabilidad', 'Cumple con sus funciones docentes'],
            ['apoderado', 'Compromiso', 'Demuestra interés por sus estudiantes'],
        ] as [$type, $name, $description]) {
            EvaluationCriterion::firstOrCreate([
                'evaluation_period_id' => $period->id,
                'evaluator_type' => $type,
                'name' => $name,
            ], ['description' => $description, 'is_active' => true]);
        }
    }
}
