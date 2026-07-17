<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\LateFeeSetting;
use App\Models\Level;
use App\Models\PaymentConcept;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const PASSWORD = '123456';

    public function run(): void
    {
        $this->call(DemoUsersSeeder::class);

        DB::transaction(function (): void {
            $admin = $this->user('administrador', 'Administrador', 'admin@school.com');
            $secretary = $this->user('secretaria', 'Secretaria', 'secretaria@school.com');

            $years = $this->academicYears();
            $catalog = $this->academicCatalog();
            $teachers = $this->teachers();
            $students = $this->students($admin);
            $courses = $this->courses($catalog);

            $this->teacherAssignments($years['2026'], $catalog, $teachers, $courses);
            $enrollments = $this->enrollments($years['2026'], $catalog, $students, $admin);
            $concepts = $this->paymentConcepts($years['2026']);
            $payments = $this->studentPayments($enrollments, $concepts, $admin, $secretary);
            $this->lateFeeSettings($years, $admin);
            $this->announcements($years['2026'], $catalog, $teachers, $students, $payments, $admin);
            $this->evaluations($teachers, $students);
        });
    }

    private function user(string $role, string $name, string $email): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'role' => $role,
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'is_active' => true,
                'must_change_password' => false,
                'access_created_automatically' => false,
            ]
        );
    }

    /**
     * @return array<string, AcademicYear>
     */
    private function academicYears(): array
    {
        $items = [
            2026 => ['starts_at' => '2026-03-01', 'ends_at' => '2026-12-20', 'status' => 'activo'],
            2025 => ['starts_at' => '2025-03-03', 'ends_at' => '2025-12-19', 'status' => 'cerrado'],
            2027 => ['starts_at' => '2027-03-01', 'ends_at' => '2027-12-18', 'status' => 'inactivo'],
        ];

        $years = [];
        foreach ($items as $year => $data) {
            $years[(string) $year] = AcademicYear::updateOrCreate(['year' => $year], $data);
        }

        return $years;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function academicCatalog(): array
    {
        $structure = [
            'Inicial' => ['3 años', '4 años', '5 años'],
            'Primaria' => ['1° grado', '2° grado', '3° grado', '4° grado', '5° grado', '6° grado'],
        ];

        $catalog = [];
        foreach ($structure as $levelName => $gradeNames) {
            $level = Level::updateOrCreate(['name' => $levelName], ['status' => 'activo']);
            $catalog[$levelName] = ['level' => $level, 'grades' => []];

            foreach ($gradeNames as $index => $gradeName) {
                $catalog[$levelName]['grades'][$gradeName] = Grade::updateOrCreate(
                    ['level_id' => $level->id, 'name' => $gradeName],
                    ['sort_order' => $index + 1, 'status' => 'activo']
                );
            }
        }

        return $catalog;
    }

    /**
     * @return array<string, Teacher>
     */
    private function teachers(): array
    {
        $rows = [
            ['DOC-001', 'docente@school.com', 'Carlos', 'Gómez Silva', '40000002', '988777666', 'Matemática'],
            ['DOC-002', 'docente.comunicacion@school.com', 'María Elena', 'Torres Vega', '40000003', '988777667', 'Comunicación'],
            ['DOC-003', 'docente.inicial@school.com', 'Rosa', 'Quispe Huamán', '40000004', '988777668', 'Inicial'],
            ['DOC-004', 'docente.ciencias@school.com', 'Jorge Luis', 'Medina Paredes', '40000005', '988777669', 'Ciencia y Tecnología'],
            ['DOC-005', 'docente.ingles@school.com', 'Patricia', 'Salazar León', '40000006', '988777670', 'Inglés'],
            ['DOC-006', 'docente.arte@school.com', 'Miguel Ángel', 'Reyes Soto', '40000007', '988777671', 'Arte y Educación Física'],
            ['DOC-007', 'docente.religion@school.com', 'Carmen', 'Rojas Molina', '40000008', '988777672', 'Educación Religiosa'],
            ['DOC-008', 'docente.tutoria@school.com', 'Fernando', 'Pacheco Ruiz', '40000009', '988777673', 'Tutoría'],
            ['DOC-009', 'docente.computacion@school.com', 'Diana', 'Campos Ibarra', '40000010', '988777674', 'Computación'],
            ['DOC-010', 'docente.musica@school.com', 'Alberto', 'Núñez Vera', '40000011', '988777675', 'Música'],
            ['DOC-011', 'docente.danza@school.com', 'Elena', 'Mendoza León', '40000012', '988777676', 'Danza'],
            ['DOC-012', 'docente.psicologia@school.com', 'Sonia', 'Arias Palacios', '40000013', '988777677', 'Psicología Educativa'],
            ['DOC-013', 'docente.primaria1@school.com', 'Ricardo', 'Vega Salinas', '40000014', '988777678', 'Primaria baja'],
            ['DOC-014', 'docente.primaria2@school.com', 'Gabriela', 'Flores Mejía', '40000015', '988777679', 'Primaria alta'],
            ['DOC-015', 'docente.coordinacion@school.com', 'Hugo', 'Delgado Ponce', '40000016', '988777680', 'Coordinación académica'],
        ];

        $teachers = [];
        foreach ($rows as [$code, $email, $firstNames, $lastNames, $dni, $phone, $specialty]) {
            $user = $this->user('docente', trim("{$firstNames} {$lastNames}"), $email);
            $teachers[$code] = Teacher::updateOrCreate(
                ['code' => $code],
                [
                    'user_id' => $user->id,
                    'first_names' => $firstNames,
                    'last_names' => $lastNames,
                    'dni' => $dni,
                    'phone' => $phone,
                    'email' => $email,
                    'specialty' => $specialty,
                    'status' => 'activo',
                ]
            );
        }

        return $teachers;
    }

    /**
     * @return array<int, Student>
     */
    private function students(User $admin): array
    {
        $guardianFirstNames = ['María', 'Roberto', 'Claudia', 'Luis', 'Ana', 'Víctor', 'Ruth', 'César', 'Natalia', 'Óscar', 'Paola', 'Daniel', 'Verónica', 'Eduardo', 'Silvia', 'Raúl', 'Teresa', 'Jaime', 'Milagros', 'Gustavo', 'Karina', 'Marco', 'Lorena', 'Andrés', 'Pilar', 'Héctor', 'Fiorella', 'Sergio', 'Beatriz', 'Renzo', 'Mónica', 'Alonso', 'Roxana', 'Iván', 'Patricia', 'Julio', 'Gabriela', 'Tomás', 'Lucero', 'Manuel', 'Camila', 'Javier', 'Elisa', 'Martín', 'Carolina'];
        $familyNames = ['Ramos Flores', 'Castro Peña', 'Vargas Ríos', 'López Cárdenas', 'Mendoza Díaz', 'Fernández Cruz', 'Salas Núñez', 'Ríos Aguilar', 'Paredes Soto', 'Quispe Arias', 'Cárdenas Vargas', 'Silva López', 'León Mendoza', 'Cruz Fernández', 'Núñez Salas', 'Aguilar Ríos', 'Torres Vega', 'Medina Paredes', 'Reyes Soto', 'Campos Ibarra', 'Arias Palacios', 'Delgado Ponce', 'Vega Salinas', 'Flores Mejía', 'Molina Rojas', 'Pacheco Ruiz', 'Huamán Bravo', 'Rojas Molina', 'Navarro Díaz', 'Sánchez Prado', 'Ortega Luna', 'Mejía Castro', 'Bravo Espinoza', 'Palacios Benites', 'Ibarra Herrera', 'Salinas Quiroz', 'Ponce Valdez', 'Herrera Peña', 'Valdez Rojas', 'Quiroz León', 'Benites Campos', 'Espinoza Vega', 'Prado Salas', 'Luna Medina', 'Soto Reyes'];
        $streets = ['Av. Los Jazmines', 'Jr. Las Palmeras', 'Calle San Martín', 'Av. Central', 'Pasaje Los Olivos', 'Av. Perú', 'Jr. Amazonas', 'Calle Unión', 'Av. Universitaria', 'Jr. Libertad'];
        $relationships = ['Madre', 'Padre', 'Apoderado'];

        $guardianRows = [];
        for ($i = 1; $i <= 45; $i++) {
            $firstNames = $guardianFirstNames[$i - 1];
            $lastName = $familyNames[$i - 1];
            $slug = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $guardianRows[] = [
                "APO-{$slug}",
                $i === 1 ? 'apoderado@school.com' : "apoderado{$slug}@school.com",
                $firstNames,
                $lastName,
                (string) (40010000 + $i),
                (string) (999888700 + $i),
                "apoderado{$slug}.contacto@school.com",
                $streets[($i - 1) % count($streets)].' '.(100 + $i),
                $relationships[($i - 1) % count($relationships)],
            ];
        }

        $guardians = [];
        foreach ($guardianRows as [$key, $email, $firstNames, $lastNamesValue, $dni, $phone, $contactEmail, $address, $relationship]) {
            $user = $this->user('apoderado', trim("{$firstNames} {$lastNamesValue}"), $email);
            $guardians[$key] = Guardian::updateOrCreate(
                ['dni' => $dni],
                [
                    'user_id' => $user->id,
                    'first_names' => $firstNames,
                    'last_names' => $lastNamesValue,
                    'phone' => $phone,
                    'email' => $contactEmail,
                    'address' => $address,
                    'relationship' => $relationship,
                    'status' => 'activo',
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }

        $femaleNames = ['Lucía', 'Valentina', 'Sofía', 'Camila', 'Martina', 'Renata', 'Antonella', 'Isabella', 'Mariana', 'Alessia', 'Daniela', 'Regina', 'Ariana', 'Emma', 'Catalina', 'Fernanda', 'Bianca', 'Micaela', 'Andrea', 'Ximena', 'Paula', 'Jimena', 'Romina', 'Victoria', 'Maite', 'Abril', 'Danna', 'Carla', 'Valeria', 'Elena', 'Clara', 'Josefina', 'Miranda', 'Florencia', 'Gabriela'];
        $maleNames = ['Mateo', 'Diego', 'Sebastián', 'Thiago', 'Alejandro', 'Nicolás', 'Emiliano', 'Gabriel', 'Rodrigo', 'Adrián', 'Santiago', 'Leonardo', 'Bruno', 'Facundo', 'Ignacio', 'Joaquín', 'Matías', 'Tomás', 'Lucas', 'Samuel', 'Gael', 'Benjamín', 'Dylan', 'Iker', 'Álvaro', 'Franco', 'Mauricio', 'Cristóbal', 'Piero', 'Esteban', 'Fabián', 'Gonzalo', 'Rafael', 'Pablo', 'Hernán'];

        $studentRows = [];
        for ($i = 1; $i <= 70; $i++) {
            $isFemale = $i % 2 === 1;
            $firstNames = $isFemale ? $femaleNames[(int) (($i - 1) / 2) % count($femaleNames)] : $maleNames[(int) (($i - 2) / 2) % count($maleNames)];
            $lastName = $familyNames[($i - 1) % count($familyNames)];
            if ($i === 1) {
                $lastName = 'Pérez Ramos';
            }
            $slug = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $guardianNumber = (($i - 1) % count($guardianRows)) + 1;
            $secondaryGuardianNumber = $i % 5 === 0 ? (($guardianNumber % count($guardianRows)) + 1) : null;

            $studentRows[] = [
                "ALU-{$slug}",
                $i === 1 ? 'alumno@school.com' : "alumno{$slug}@school.com",
                $firstNames,
                $lastName,
                (string) (70000000 + $i),
                sprintf('%d-%02d-%02d', 2012 + ($i % 9), (($i - 1) % 12) + 1, (($i - 1) % 27) + 1),
                $isFemale ? 'Femenino' : 'Masculino',
                $streets[($i - 1) % count($streets)].' '.(200 + $i),
                'APO-'.str_pad((string) $guardianNumber, 3, '0', STR_PAD_LEFT),
                $secondaryGuardianNumber ? 'APO-'.str_pad((string) $secondaryGuardianNumber, 3, '0', STR_PAD_LEFT) : null,
            ];
        }

        $students = [];
        foreach ($studentRows as [$code, $email, $firstNames, $lastNames, $dni, $birthDate, $gender, $address, $primaryGuardian, $secondaryGuardian]) {
            $user = $this->user('alumno', trim("{$firstNames} {$lastNames}"), $email);
            $student = Student::updateOrCreate(
                ['code' => $code],
                [
                    'user_id' => $user->id,
                    'first_names' => $firstNames,
                    'last_names' => $lastNames,
                    'dni' => $dni,
                    'birth_date' => $birthDate,
                    'gender' => $gender,
                    'address' => $address,
                    'status' => 'activo',
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );

            $sync = [
                $guardians[$primaryGuardian]->id => [
                    'relationship' => $guardians[$primaryGuardian]->relationship,
                    'is_primary' => true,
                    'status' => 'activo',
                    'created_by' => $admin->id,
                ],
            ];

            if ($secondaryGuardian && isset($guardians[$secondaryGuardian])) {
                $sync[$guardians[$secondaryGuardian]->id] = [
                    'relationship' => $guardians[$secondaryGuardian]->relationship,
                    'is_primary' => false,
                    'status' => 'activo',
                    'created_by' => $admin->id,
                ];
            }

            $student->guardians()->syncWithoutDetaching($sync);
            foreach ($sync as $guardianId => $pivot) {
                $student->guardians()->updateExistingPivot($guardianId, $pivot);
            }

            $students[] = $student;
        }

        return $students;
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<string, Course>
     */
    private function courses(array $catalog): array
    {
        $definitions = [
            'Inicial' => [
                ['COM', 'Comunicación'],
                ['MAT', 'Matemática'],
                ['PSI', 'Psicomotricidad'],
                ['ART', 'Arte y creatividad'],
            ],
            'Primaria' => [
                ['MAT', 'Matemática'],
                ['COM', 'Comunicación'],
                ['CTA', 'Ciencia y Tecnología'],
                ['PER', 'Personal Social'],
                ['ING', 'Inglés'],
                ['EDF', 'Educación Física'],
            ],
        ];

        $courses = [];
        foreach ($catalog as $levelName => $levelData) {
            $levelCode = $levelName === 'Inicial' ? 'INI' : 'PRI';
            foreach ($levelData['grades'] as $gradeName => $grade) {
                $gradeCode = str_pad((string) $grade->sort_order, 2, '0', STR_PAD_LEFT);
                foreach ($definitions[$levelName] as [$courseCode, $courseName]) {
                    $code = "{$levelCode}-{$gradeCode}-{$courseCode}";
                    $courses[$code] = Course::updateOrCreate(
                        ['code' => $code],
                        [
                            'level_id' => $levelData['level']->id,
                            'grade_id' => $grade->id,
                            'name' => $courseName,
                            'status' => 'activo',
                        ]
                    );
                }
            }
        }

        return $courses;
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @param  array<string, Teacher>  $teachers
     * @param  array<string, Course>  $courses
     */
    private function teacherAssignments(AcademicYear $year, array $catalog, array $teachers, array $courses): void
    {
        $teacherPool = array_values($teachers);
        $assignmentIndex = 0;

        foreach ($catalog as $levelName => $levelData) {
            $levelCode = $levelName === 'Inicial' ? 'INI' : 'PRI';
            foreach ($levelData['grades'] as $grade) {
                $gradeCode = str_pad((string) $grade->sort_order, 2, '0', STR_PAD_LEFT);
                foreach (['A', 'B', 'C'] as $section) {
                    foreach ($courses as $courseCode => $course) {
                        if (! str_starts_with($courseCode, "{$levelCode}-{$gradeCode}-")) {
                            continue;
                        }

                        $teacher = $teacherPool[$assignmentIndex % count($teacherPool)];
                        $assignmentIndex++;

                        DB::table('teacher_assignments')->updateOrInsert(
                            [
                                'academic_year_id' => $year->id,
                                'teacher_id' => $teacher->id,
                                'course_id' => $course->id,
                                'grade_id' => $grade->id,
                                'section' => $section,
                            ],
                            [
                                'level_id' => $levelData['level']->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @param  array<int, Student>  $students
     * @return array<int, Enrollment>
     */
    private function enrollments(AcademicYear $year, array $catalog, array $students, User $admin): array
    {
        $placements = [
            ['Inicial', '3 años', 'A', 'matriculado'],
            ['Inicial', '3 años', 'B', 'matriculado'],
            ['Inicial', '3 años', 'C', 'pendiente'],
            ['Inicial', '4 años', 'A', 'matriculado'],
            ['Inicial', '4 años', 'B', 'observado'],
            ['Inicial', '4 años', 'C', 'matriculado'],
            ['Inicial', '5 años', 'A', 'matriculado'],
            ['Inicial', '5 años', 'B', 'matriculado'],
            ['Inicial', '5 años', 'C', 'matriculado'],
            ['Primaria', '1° grado', 'A', 'matriculado'],
            ['Primaria', '1° grado', 'B', 'matriculado'],
            ['Primaria', '1° grado', 'C', 'matriculado'],
            ['Primaria', '2° grado', 'A', 'matriculado'],
            ['Primaria', '2° grado', 'B', 'matriculado'],
            ['Primaria', '2° grado', 'C', 'pendiente'],
            ['Primaria', '3° grado', 'A', 'matriculado'],
            ['Primaria', '3° grado', 'B', 'matriculado'],
            ['Primaria', '3° grado', 'C', 'matriculado'],
            ['Primaria', '4° grado', 'A', 'matriculado'],
            ['Primaria', '4° grado', 'B', 'observado'],
            ['Primaria', '4° grado', 'C', 'matriculado'],
            ['Primaria', '5° grado', 'A', 'matriculado'],
            ['Primaria', '5° grado', 'B', 'matriculado'],
            ['Primaria', '5° grado', 'C', 'matriculado'],
            ['Primaria', '6° grado', 'A', 'matriculado'],
            ['Primaria', '6° grado', 'B', 'matriculado'],
            ['Primaria', '6° grado', 'C', 'retirado'],
        ];

        $enrollments = [];
        foreach ($students as $index => $student) {
            [$levelName, $gradeName, $section, $status] = $placements[$index % count($placements)];
            $level = $catalog[$levelName]['level'];
            $grade = $catalog[$levelName]['grades'][$gradeName];
            $guardian = $student->guardians()->wherePivot('is_primary', true)->first();

            $enrollments[] = Enrollment::updateOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $year->id],
                [
                    'guardian_id' => $guardian?->id,
                    'level_id' => $level->id,
                    'grade_id' => $grade->id,
                    'section' => $section,
                    'enrolled_at' => '2026-03-01',
                    'status' => $status,
                    'observations' => $status === 'observado' ? 'Documentación pendiente de regularización.' : null,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }

        return $enrollments;
    }

    /**
     * @return array<int, PaymentConcept>
     */
    private function paymentConcepts(AcademicYear $year): array
    {
        $concepts = [];
        $concepts[] = PaymentConcept::updateOrCreate(
            ['academic_year_id' => $year->id, 'type' => 'matricula', 'month' => null],
            [
                'name' => 'Matrícula 2026',
                'description' => 'Pago único de matrícula del año escolar.',
                'amount' => 250,
                'due_date' => '2026-03-01',
                'status' => 'activo',
                'sort_order' => 1,
            ]
        );

        $months = [
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
        ];

        foreach ($months as $month => $name) {
            $concepts[] = PaymentConcept::updateOrCreate(
                ['academic_year_id' => $year->id, 'type' => 'mensualidad', 'month' => $month],
                [
                    'name' => "Mensualidad {$name}",
                    'description' => "Pensión escolar correspondiente a {$name}.",
                    'amount' => 180,
                    'due_date' => sprintf('2026-%02d-05', $month),
                    'status' => 'activo',
                    'sort_order' => $month,
                ]
            );
        }

        return $concepts;
    }

    /**
     * @param  array<int, Enrollment>  $enrollments
     * @param  array<int, PaymentConcept>  $concepts
     * @return array<int, StudentPayment>
     */
    private function studentPayments(array $enrollments, array $concepts, User $admin, User $secretary): array
    {
        $payments = [];
        $methods = ['efectivo', 'transferencia', 'yape', 'plin', 'tarjeta'];

        foreach ($enrollments as $studentIndex => $enrollment) {
            foreach ($concepts as $conceptIndex => $concept) {
                $amount = (float) $concept->amount;
                $status = 'pendiente';
                $amountPaid = 0;
                $lateFee = 0;
                $paidAt = null;
                $method = null;
                $receipt = null;
                $lateAppliedAt = null;
                $examBlocked = false;
                $examBlockedAt = null;
                $noticeGeneratedAt = null;

                if ($concept->type === 'matricula' || in_array((int) $concept->month, [3, 4], true)) {
                    $status = 'pagado';
                    $amountPaid = $amount;
                    $paidAt = $concept->due_date?->copy()->addDays(($studentIndex % 4) + 1)->toDateString();
                    $method = $methods[$studentIndex % count($methods)];
                    $receipt = sprintf('REC-2026-%03d-%02d', $studentIndex + 1, $conceptIndex + 1);
                } elseif ((int) $concept->month === 5) {
                    $status = $studentIndex % 3 === 0 ? 'parcial' : 'pagado';
                    $amountPaid = $status === 'parcial' ? 90 : $amount;
                    $paidAt = $status === 'pagado' ? '2026-05-06' : '2026-05-10';
                    $method = $methods[$studentIndex % count($methods)];
                    $receipt = $status === 'pagado' ? sprintf('REC-2026-%03d-%02d', $studentIndex + 1, $conceptIndex + 1) : null;
                } elseif ((int) $concept->month === 6) {
                    $status = $studentIndex % 4 === 0 ? 'vencido' : 'pendiente';
                    if ($status === 'vencido') {
                        $lateFee = round($amount * 0.05, 2);
                        $lateAppliedAt = '2026-06-12 08:00:00';
                        $examBlocked = true;
                        $examBlockedAt = '2026-06-12 08:00:00';
                        $noticeGeneratedAt = '2026-06-12 08:05:00';
                    }
                } elseif ((int) $concept->month === 7) {
                    $status = $studentIndex % 5 === 0 ? 'anulado' : 'pendiente';
                }

                $total = $amount + $lateFee;
                $payments[] = StudentPayment::updateOrCreate(
                    ['student_id' => $enrollment->student_id, 'payment_concept_id' => $concept->id],
                    [
                        'enrollment_id' => $enrollment->id,
                        'amount' => $amount,
                        'original_amount' => $amount,
                        'late_fee_amount' => $lateFee,
                        'total_amount' => $total,
                        'amount_paid' => $amountPaid,
                        'status' => $status,
                        'due_date' => $concept->due_date?->toDateString(),
                        'late_fee_applied_at' => $lateAppliedAt,
                        'exam_blocked' => $examBlocked,
                        'exam_blocked_at' => $examBlockedAt,
                        'exam_unblocked_at' => $status === 'pagado' ? $paidAt : null,
                        'notice_generated_at' => $noticeGeneratedAt,
                        'paid_at' => $paidAt,
                        'payment_method' => $method,
                        'paid_by_user_id' => $status === 'pagado' ? $enrollment->guardian?->user_id : null,
                        'registered_by' => in_array($status, ['pagado', 'parcial'], true) ? $secretary->id : null,
                        'receipt_number' => $receipt,
                        'observations' => $status === 'anulado' ? 'Registro anulado para pruebas de flujo administrativo.' : null,
                        'cancelled_at' => $status === 'anulado' ? now() : null,
                        'cancelled_reason' => $status === 'anulado' ? 'Concepto no aplicable al alumno.' : null,
                    ]
                );
            }
        }

        return $payments;
    }

    /**
     * @param  array<string, AcademicYear>  $years
     */
    private function lateFeeSettings(array $years, User $admin): void
    {
        foreach ($years as $year) {
            LateFeeSetting::updateOrCreate(
                ['academic_year_id' => $year->id, 'name' => "Mora {$year->year}"],
                [
                    'grace_days' => 5,
                    'late_fee_percentage' => 5,
                    'blocks_exam_right' => true,
                    'auto_generate_notice' => true,
                    'notice_title' => 'Aviso de mora pendiente',
                    'notice_message' => 'Estimado apoderado, se informa que el alumno [NOMBRE_ALUMNO] mantiene una mensualidad vencida correspondiente a [CONCEPTO]. Total a pagar: S/ [TOTAL_A_PAGAR].',
                    'status' => $year->status === 'activo' ? 'activo' : 'inactivo',
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @param  array<string, Teacher>  $teachers
     * @param  array<int, Student>  $students
     * @param  array<int, StudentPayment>  $payments
     */
    private function announcements(AcademicYear $year, array $catalog, array $teachers, array $students, array $payments, User $admin): void
    {
        $firstStudent = $students[0];
        $firstGuardian = $firstStudent->guardians()->first();
        $firstPaymentWithLateFee = collect($payments)->first(fn (StudentPayment $payment) => (float) $payment->late_fee_amount > 0);

        $rows = [
            [
                'title' => 'Bienvenida al año escolar 2026',
                'message' => 'Bienvenidos al año escolar 2026. Les deseamos un ciclo de aprendizaje ordenado, cercano y participativo.',
                'type' => 'general',
                'priority' => 'normal',
                'target_type' => 'all_users',
            ],
            [
                'title' => 'Reunión general de apoderados',
                'message' => 'La reunión general de apoderados se realizará el viernes a las 6:00 p.m. en el auditorio principal.',
                'type' => 'academico',
                'priority' => 'alta',
                'target_type' => 'all_guardians',
            ],
            [
                'title' => 'Evaluación de Matemática',
                'message' => 'Los estudiantes de 1° grado A tendrán evaluación de Matemática el próximo martes.',
                'type' => 'examen',
                'priority' => 'normal',
                'target_type' => 'classroom',
                'level_id' => $catalog['Primaria']['level']->id,
                'grade_id' => $catalog['Primaria']['grades']['1° grado']->id,
                'section' => 'A',
            ],
            [
                'title' => 'Materiales de aula',
                'message' => 'Se solicita revisar la lista de materiales pendientes para las actividades de comunicación.',
                'type' => 'academico',
                'priority' => 'baja',
                'target_type' => 'student',
                'student_id' => $firstStudent->id,
            ],
            [
                'title' => 'Coordinación con apoderado',
                'message' => 'La secretaría solicita acercarse para actualizar los datos de contacto registrados.',
                'type' => 'otro',
                'priority' => 'normal',
                'target_type' => 'guardian',
                'guardian_id' => $firstGuardian?->id,
            ],
            [
                'title' => 'Capacitación docente',
                'message' => 'El equipo docente participará en una capacitación interna sobre evaluación formativa.',
                'type' => 'general',
                'priority' => 'normal',
                'target_type' => 'teacher',
                'teacher_id' => $teachers['DOC-001']->id,
            ],
        ];

        if ($firstPaymentWithLateFee) {
            $rows[] = [
                'title' => 'Aviso de mora pendiente',
                'message' => 'Se registra una mensualidad vencida con mora aplicada. Por favor regularizar el pago.',
                'type' => 'mora',
                'priority' => 'urgente',
                'target_type' => 'guardian',
                'guardian_id' => $firstPaymentWithLateFee->student->guardians()->first()?->id,
                'student_id' => $firstPaymentWithLateFee->student_id,
                'student_payment_id' => $firstPaymentWithLateFee->id,
            ];
        }

        foreach ($rows as $index => $row) {
            $announcement = Announcement::updateOrCreate(
                [
                    'title' => $row['title'],
                    'type' => $row['type'],
                    'student_payment_id' => $row['student_payment_id'] ?? null,
                ],
                [
                    'message' => $row['message'],
                    'priority' => $row['priority'],
                    'target_type' => $row['target_type'],
                    'academic_year_id' => $year->id,
                    'level_id' => $row['level_id'] ?? null,
                    'grade_id' => $row['grade_id'] ?? null,
                    'section' => $row['section'] ?? null,
                    'student_id' => $row['student_id'] ?? null,
                    'guardian_id' => $row['guardian_id'] ?? null,
                    'teacher_id' => $row['teacher_id'] ?? null,
                    'student_payment_id' => $row['student_payment_id'] ?? null,
                    'publish_at' => now()->subDays(10 - $index),
                    'expires_at' => now()->addMonths(2),
                    'status' => 'published',
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );

            $this->announcementRecipients($announcement, $students, $teachers);
        }
    }

    /**
     * @param  array<int, Student>  $students
     * @param  array<string, Teacher>  $teachers
     */
    private function announcementRecipients(Announcement $announcement, array $students, array $teachers): void
    {
        $users = match ($announcement->target_type) {
            'all_users' => User::where('is_active', true)->pluck('id')->all(),
            'all_guardians' => User::where('role', 'apoderado')->pluck('id')->all(),
            'teacher' => [$announcement->teacher?->user_id],
            'student' => [$announcement->student?->user_id],
            'guardian' => [$announcement->guardian?->user_id],
            'classroom' => Enrollment::where('academic_year_id', $announcement->academic_year_id)
                ->where('grade_id', $announcement->grade_id)
                ->where('section', $announcement->section)
                ->where('status', 'matriculado')
                ->with('student')
                ->get()
                ->pluck('student.user_id')
                ->filter()
                ->all(),
            default => [],
        };

        foreach (array_filter($users) as $index => $userId) {
            AnnouncementRecipient::updateOrCreate(
                ['announcement_id' => $announcement->id, 'user_id' => $userId],
                [
                    'student_id' => $announcement->student_id,
                    'guardian_id' => $announcement->guardian_id,
                    'delivered_at' => now()->subDays(3),
                    'read_at' => $index % 2 === 0 ? now()->subDay() : null,
                    'dismissed_at' => null,
                ]
            );
        }
    }

    /**
     * @param  array<string, Teacher>  $teachers
     * @param  array<int, Student>  $students
     */
    private function evaluations(array $teachers, array $students): void
    {
        $period = EvaluationPeriod::updateOrCreate(
            ['name' => 'Evaluación Docente 2026 - I Bimestre'],
            [
                'starts_at' => now()->subMonth()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
                'status' => 'activo',
            ]
        );

        $criteriaRows = [
            ['alumno', 'Explicación clara', 'El docente explica los temas de forma comprensible.'],
            ['alumno', 'Trato respetuoso', 'El docente mantiene una relación respetuosa con los estudiantes.'],
            ['alumno', 'Uso de actividades', 'El docente propone actividades que ayudan a aprender.'],
            ['alumno', 'Puntualidad', 'El docente cumple con los horarios de clase.'],
            ['apoderado', 'Comunicación con la familia', 'El docente informa avances y dificultades oportunamente.'],
            ['apoderado', 'Responsabilidad', 'El docente cumple con sus compromisos académicos.'],
            ['apoderado', 'Acompañamiento', 'El docente acompaña el progreso del estudiante.'],
            ['apoderado', 'Organización', 'El docente organiza clases y tareas de forma clara.'],
        ];

        foreach ($criteriaRows as [$type, $name, $description]) {
            EvaluationCriterion::updateOrCreate(
                ['evaluation_period_id' => $period->id, 'evaluator_type' => $type, 'name' => $name],
                ['description' => $description, 'is_active' => true]
            );
        }

        $evaluationRows = [
            [$students[2], null, $teachers['DOC-003'], 'alumno', 'Me gustan sus actividades.', [5, 5, 4, 5]],
            [$students[3], null, $teachers['DOC-003'], 'alumno', 'Explica con paciencia.', [5, 4, 4, 5]],
            [$students[8], null, $teachers['DOC-002'], 'alumno', 'La clase es ordenada.', [4, 5, 4, 4]],
            [null, $students[4]->guardians()->first(), $teachers['DOC-003'], 'apoderado', 'Mantiene buena comunicación.', [5, 4, 5, 4]],
            [null, $students[10]->guardians()->first(), $teachers['DOC-004'], 'apoderado', 'Se observa seguimiento constante.', [4, 4, 5, 4]],
        ];

        foreach ($evaluationRows as [$student, $guardian, $teacher, $type, $comment, $scores]) {
            $user = $type === 'alumno' ? $student?->user : $guardian?->user;
            if (! $user) {
                continue;
            }

            $evaluation = TeacherEvaluation::updateOrCreate(
                ['evaluation_period_id' => $period->id, 'teacher_id' => $teacher->id, 'user_id' => $user->id],
                [
                    'student_id' => $student?->id,
                    'guardian_id' => $guardian?->id,
                    'evaluator_type' => $type,
                    'average_score' => round(collect($scores)->avg(), 2),
                    'comment' => $comment,
                ]
            );

            $criteria = EvaluationCriterion::where('evaluation_period_id', $period->id)
                ->where('evaluator_type', $type)
                ->orderBy('id')
                ->get();

            foreach ($criteria as $index => $criterion) {
                EvaluationDetail::updateOrCreate(
                    ['teacher_evaluation_id' => $evaluation->id, 'evaluation_criterion_id' => $criterion->id],
                    ['score' => $scores[$index] ?? 4]
                );
            }
        }
    }
}
