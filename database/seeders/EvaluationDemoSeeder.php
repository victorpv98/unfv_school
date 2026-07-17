<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluationDemoSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{name: string, description: string}>>
     */
    private array $criteria = [
        'alumno' => [
            ['name' => 'Explicación clara', 'description' => 'El docente explica los temas de forma comprensible.'],
            ['name' => 'Trato respetuoso', 'description' => 'El docente mantiene una relación respetuosa con los estudiantes.'],
            ['name' => 'Actividades de aprendizaje', 'description' => 'El docente propone actividades que ayudan a aprender.'],
            ['name' => 'Puntualidad y orden', 'description' => 'El docente cumple horarios y organiza la clase.'],
        ],
        'apoderado' => [
            ['name' => 'Comunicación con la familia', 'description' => 'El docente informa avances y dificultades oportunamente.'],
            ['name' => 'Responsabilidad', 'description' => 'El docente cumple sus compromisos académicos.'],
            ['name' => 'Acompañamiento', 'description' => 'El docente acompaña el progreso del estudiante.'],
            ['name' => 'Organización', 'description' => 'El docente organiza clases y tareas de forma clara.'],
        ],
    ];

    /**
     * @var array<int, string>
     */
    private array $studentComments = [
        'Explica con paciencia y responde nuestras preguntas.',
        'Sus clases son ordenadas y fáciles de seguir.',
        'Usa buenos ejemplos en clase.',
        'Me ayuda cuando tengo dudas.',
        'Las actividades hacen que el tema se entienda mejor.',
        'Mantiene buen trato con todos.',
    ];

    /**
     * @var array<int, string>
     */
    private array $guardianComments = [
        'Mantiene buena comunicación con la familia.',
        'Se observa seguimiento constante del aprendizaje.',
        'Informa oportunamente sobre avances y dificultades.',
        'Organiza bien las tareas y recomendaciones.',
        'Muestra compromiso con el progreso del estudiante.',
        'La orientación brindada ha sido clara.',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $periods = $this->periods();
            $this->criteriaFor($periods);

            $enrollments = Enrollment::query()
                ->with(['student.user', 'student.guardians.user'])
                ->where('status', 'matriculado')
                ->orderBy('id')
                ->get();

            foreach ($enrollments as $index => $enrollment) {
                $teachers = $this->teachersForEnrollment($enrollment);
                if ($teachers->isEmpty() || ! $enrollment->student?->user) {
                    continue;
                }

                $this->seedStudentEvaluations($enrollment, $teachers, $periods, $index);
                $this->seedGuardianEvaluations($enrollment, $teachers, $periods, $index);
            }
        });
    }

    /**
     * @return array<string, EvaluationPeriod>
     */
    private function periods(): array
    {
        $definitions = [
            'student_s1' => ['Evaluación alumno 2026 I semestre', '2026-01-01', '2026-06-30'],
            'student_s2' => ['Evaluación alumno 2026 II semestre', '2026-07-01', '2026-12-31'],
            'guardian_q1' => ['Evaluación apoderado 2026 trimestre 1', '2026-01-01', '2026-03-31'],
            'guardian_q2' => ['Evaluación apoderado 2026 trimestre 2', '2026-04-01', '2026-06-30'],
            'guardian_q3' => ['Evaluación apoderado 2026 trimestre 3', '2026-07-01', '2026-09-30'],
            'guardian_q4' => ['Evaluación apoderado 2026 trimestre 4', '2026-10-01', '2026-12-31'],
        ];

        $periods = [];
        foreach ($definitions as $key => [$name, $startsAt, $endsAt]) {
            $periods[$key] = EvaluationPeriod::updateOrCreate(
                ['name' => $name],
                ['starts_at' => $startsAt, 'ends_at' => $endsAt, 'status' => 'activo']
            );
        }

        return $periods;
    }

    /**
     * @param  array<string, EvaluationPeriod>  $periods
     */
    private function criteriaFor(array $periods): void
    {
        foreach ($periods as $key => $period) {
            $type = str_starts_with($key, 'student') ? 'alumno' : 'apoderado';

            foreach ($this->criteria[$type] as $criterion) {
                EvaluationCriterion::updateOrCreate(
                    [
                        'evaluation_period_id' => $period->id,
                        'evaluator_type' => $type,
                        'name' => $criterion['name'],
                    ],
                    [
                        'description' => $criterion['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function teachersForEnrollment(Enrollment $enrollment)
    {
        return Teacher::query()
            ->whereHas('courses', function ($query) use ($enrollment) {
                $query->where('teacher_assignments.academic_year_id', $enrollment->academic_year_id)
                    ->where('teacher_assignments.grade_id', $enrollment->grade_id)
                    ->where('teacher_assignments.section', $enrollment->section);
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, EvaluationPeriod>  $periods
     */
    private function seedStudentEvaluations(Enrollment $enrollment, $teachers, array $periods, int $index): void
    {
        $student = $enrollment->student;
        $periodDefinitions = [
            ['period' => $periods['student_s1'], 'date' => Carbon::parse('2026-05-20 10:00:00'), 'offset' => 0],
            ['period' => $periods['student_s2'], 'date' => Carbon::parse('2026-07-15 10:00:00'), 'offset' => 2],
        ];

        foreach ($periodDefinitions as $definition) {
            foreach ($teachers->skip($definition['offset'])->take(2) as $teacherOffset => $teacher) {
                $scores = $this->scores($index + $teacherOffset, 4);
                $this->createEvaluation(
                    $definition['period'],
                    $teacher,
                    $student->user_id,
                    'alumno',
                    $scores,
                    $this->studentComments[($index + $teacherOffset) % count($this->studentComments)],
                    $definition['date']->copy()->addDays($index % 18),
                    $student->id,
                    null
                );
            }
        }
    }

    /**
     * @param  array<string, EvaluationPeriod>  $periods
     */
    private function seedGuardianEvaluations(Enrollment $enrollment, $teachers, array $periods, int $index): void
    {
        $guardian = $enrollment->student?->guardians()->wherePivot('is_primary', true)->first()
            ?? $enrollment->student?->guardians()->first();

        if (! $guardian?->user_id) {
            return;
        }

        $periodDefinitions = [
            ['period' => $periods['guardian_q1'], 'date' => Carbon::parse('2026-03-18 18:00:00'), 'offset' => 0],
            ['period' => $periods['guardian_q2'], 'date' => Carbon::parse('2026-05-25 18:00:00'), 'offset' => 1],
            ['period' => $periods['guardian_q3'], 'date' => Carbon::parse('2026-07-14 18:00:00'), 'offset' => 2],
        ];

        foreach ($periodDefinitions as $definition) {
            foreach ($teachers->skip($definition['offset'])->take(2) as $teacherOffset => $teacher) {
                $scores = $this->scores($index + $teacherOffset + 1, 4);
                $this->createEvaluation(
                    $definition['period'],
                    $teacher,
                    $guardian->user_id,
                    'apoderado',
                    $scores,
                    $this->guardianComments[($index + $teacherOffset) % count($this->guardianComments)],
                    $definition['date']->copy()->addDays($index % 12),
                    null,
                    $guardian->id
                );
            }
        }
    }

    /**
     * @param  array<int, int>  $scores
     */
    private function createEvaluation(
        EvaluationPeriod $period,
        Teacher $teacher,
        int $userId,
        string $type,
        array $scores,
        string $comment,
        Carbon $createdAt,
        ?int $studentId,
        ?int $guardianId
    ): void {
        $evaluation = TeacherEvaluation::updateOrCreate(
            [
                'evaluation_period_id' => $period->id,
                'teacher_id' => $teacher->id,
                'user_id' => $userId,
            ],
            [
                'student_id' => $studentId,
                'guardian_id' => $guardianId,
                'evaluator_type' => $type,
                'average_score' => round(collect($scores)->avg(), 2),
                'comment' => $comment,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]
        );

        $criteria = EvaluationCriterion::query()
            ->where('evaluation_period_id', $period->id)
            ->where('evaluator_type', $type)
            ->orderBy('id')
            ->get();

        foreach ($criteria as $index => $criterion) {
            EvaluationDetail::updateOrCreate(
                [
                    'teacher_evaluation_id' => $evaluation->id,
                    'evaluation_criterion_id' => $criterion->id,
                ],
                [
                    'score' => $scores[$index] ?? 4,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function scores(int $seed, int $count): array
    {
        $patterns = [
            [5, 5, 4, 5],
            [4, 5, 4, 4],
            [5, 4, 5, 4],
            [4, 4, 5, 5],
            [5, 5, 5, 4],
            [3, 4, 4, 5],
        ];

        return array_slice($patterns[$seed % count($patterns)], 0, $count);
    }
}
