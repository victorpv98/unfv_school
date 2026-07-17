<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Services\EvaluationEligibilityService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->hasRole('alumno', 'apoderado'), 403);

        $type = $this->evaluatorType();
        $period = $this->activePeriod($type);
        $window = $this->evaluationWindow($type);
        $teachers = app(EvaluationEligibilityService::class)->eligibleTeachersFor(Auth::user());
        $completed = TeacherEvaluation::query()
            ->where('user_id', Auth::id())
            ->where('evaluator_type', $type)
            ->whereBetween('created_at', [$window['starts_at'], $window['ends_at']])
            ->pluck('teacher_id')
            ->all();

        return view('evaluations.index', compact('period', 'teachers', 'completed', 'window'));
    }

    public function create(Teacher $teacher): View
    {
        abort_unless(Auth::user()->hasRole('alumno', 'apoderado'), 403);

        $type = $this->evaluatorType();
        $period = $this->activePeriod($type);
        $window = $this->evaluationWindow($type);
        abort_if(! $period, 403, 'No hay periodo de evaluación activo.');
        abort_unless(app(EvaluationEligibilityService::class)->canEvaluate(Auth::user(), $teacher), 403, 'No puedes evaluar a este docente porque no está asignado a tu matrícula.');

        $criteria = EvaluationCriterion::query()
            ->where('is_active', true)
            ->where('evaluator_type', $type)
            ->where(function ($query) use ($period) {
                $query->whereNull('evaluation_period_id')
                    ->orWhere('evaluation_period_id', $period->id)
                    ->orWhereNotNull('evaluation_period_id');
            })
            ->orderBy('id')
            ->get();

        abort_if($criteria->isEmpty(), 403, 'No hay criterios configurados para este tipo de evaluador.');
        abort_if($this->alreadyEvaluated($teacher, $type), 403, "Ya evaluaste a este docente en {$window['label']}.");

        return view('evaluations.form', compact('period', 'teacher', 'criteria', 'type', 'window'));
    }

    public function store(Request $request, Teacher $teacher): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('alumno', 'apoderado'), 403);

        $type = $this->evaluatorType();
        $period = $this->activePeriod($type);
        $window = $this->evaluationWindow($type);
        abort_if(! $period, 403, 'No hay periodo de evaluación activo.');
        abort_unless(app(EvaluationEligibilityService::class)->canEvaluate(Auth::user(), $teacher), 403, 'No puedes evaluar a este docente porque no está asignado a tu matrícula.');
        abort_if($this->alreadyEvaluated($teacher, $type), 403, "Ya evaluaste a este docente en {$window['label']}.");

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $student = Student::where('user_id', Auth::id())->first();
        $guardian = Guardian::where('user_id', Auth::id())->first();
        $average = collect($data['scores'])->avg();

        DB::transaction(function () use ($period, $teacher, $type, $student, $guardian, $data, $average) {
            $evaluation = TeacherEvaluation::create([
                'evaluation_period_id' => $period->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student?->id,
                'guardian_id' => $guardian?->id,
                'user_id' => Auth::id(),
                'evaluator_type' => $type,
                'average_score' => $average,
                'comment' => $data['comment'] ?? null,
            ]);

            foreach ($data['scores'] as $criterionId => $score) {
                EvaluationDetail::create([
                    'teacher_evaluation_id' => $evaluation->id,
                    'evaluation_criterion_id' => $criterionId,
                    'score' => $score,
                ]);
            }
        });

        return redirect()->route('evaluations.index')->with('status', 'Evaluación registrada correctamente.');
    }

    private function activePeriod(string $type): ?EvaluationPeriod
    {
        $window = $this->evaluationWindow($type);

        return EvaluationPeriod::firstOrCreate(
            ['name' => $window['period_name']],
            [
                'starts_at' => $window['starts_at']->toDateString(),
                'ends_at' => $window['ends_at']->toDateString(),
                'status' => 'activo',
            ]
        );
    }

    private function evaluatorType(): string
    {
        return Auth::user()->hasRole('apoderado') ? 'apoderado' : 'alumno';
    }

    private function alreadyEvaluated(Teacher $teacher, string $type): bool
    {
        $window = $this->evaluationWindow($type);

        return TeacherEvaluation::query()
            ->where('teacher_id', $teacher->id)
            ->where('user_id', Auth::id())
            ->where('evaluator_type', $type)
            ->whereBetween('created_at', [$window['starts_at'], $window['ends_at']])
            ->exists();
    }

    /**
     * @return array{starts_at: Carbon, ends_at: Carbon, label: string, period_name: string}
     */
    private function evaluationWindow(string $type): array
    {
        $today = now();
        $year = (int) $today->year;

        if ($type === 'alumno') {
            $firstSemester = $today->month <= 6;
            $startsAt = Carbon::create($year, $firstSemester ? 1 : 7, 1)->startOfDay();
            $endsAt = Carbon::create($year, $firstSemester ? 6 : 12, $firstSemester ? 30 : 31)->endOfDay();
            $label = $firstSemester ? 'enero - junio' : 'julio - diciembre';

            return [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'label' => $label,
                'period_name' => "Evaluación alumno {$year} ".($firstSemester ? 'I semestre' : 'II semestre'),
            ];
        }

        $quarter = (int) ceil($today->month / 3);
        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $startMonth + 2;
        $startsAt = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endsAt = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();
        $labels = [
            1 => 'enero - marzo',
            2 => 'abril - junio',
            3 => 'julio - septiembre',
            4 => 'octubre - diciembre',
        ];

        return [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'label' => $labels[$quarter],
            'period_name' => "Evaluación apoderado {$year} trimestre {$quarter}",
        ];
    }
}
