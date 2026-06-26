<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationPeriod;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\PaymentConcept;
use App\Models\Section;
use App\Models\Student;
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

        $studentUser = User::updateOrCreate(
            ['email' => 'alumno@school.test'],
            ['role' => 'alumno', 'name' => 'Alumno Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $guardianUser = User::updateOrCreate(
            ['email' => 'apoderado@school.test'],
            ['role' => 'apoderado', 'name' => 'Apoderado Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $teacherUser = User::updateOrCreate(
            ['email' => 'profesor@school.test'],
            ['role' => 'profesor', 'name' => 'Profesor Demo', 'password' => Hash::make('password'), 'is_active' => true]
        );

        $year = AcademicYear::firstOrCreate(['year' => 2026], [
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-12-20',
            'status' => 'activo',
        ]);

        $academicStructure = [
            'Inicial' => ['3 años', '4 años', '5 años'],
            'Primaria' => ['1.º', '2.º', '3.º', '4.º', '5.º', '6.º'],
            'Secundaria' => ['1.º', '2.º', '3.º', '4.º', '5.º'],
        ];

        $catalog = [];

        foreach ($academicStructure as $levelName => $gradeNames) {
            $level = Level::firstOrCreate(['name' => $levelName], ['status' => 'activo']);
            $catalog[$levelName]['level'] = $level;

            foreach ($gradeNames as $gradeName) {
                $grade = Grade::firstOrCreate(['level_id' => $level->id, 'name' => $gradeName], ['status' => 'activo']);
                $catalog[$levelName]['grades'][$gradeName] = $grade;

                foreach (['A', 'B', 'C'] as $sectionName) {
                    $section = Section::firstOrCreate(['grade_id' => $grade->id, 'name' => $sectionName], ['status' => 'activo']);
                    $catalog[$levelName]['sections'][$gradeName][$sectionName] = $section;
                }
            }
        }

        $level = $catalog['Primaria']['level'];
        $grade = $catalog['Primaria']['grades']['1.º'];
        $section = $catalog['Primaria']['sections']['1.º']['A'];

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

        $course->teachers()->syncWithoutDetaching([$teacher->id => ['academic_year_id' => $year->id, 'grade_id' => $grade->id, 'section_id' => $section->id]]);

        $student->enrollments()->firstOrCreate(['academic_year_id' => $year->id], [
            'level_id' => $level->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
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

        $period = EvaluationPeriod::firstOrCreate(['name' => 'Evaluación Demo 2026'], [
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => 'activo',
        ]);

        foreach ([
            ['alumno', 'Explicación clara', 'El profesor explica de manera entendible'],
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
