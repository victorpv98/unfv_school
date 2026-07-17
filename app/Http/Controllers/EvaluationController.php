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

        $period = $this->activePeriod();
        $teachers = app(EvaluationEligibilityService::class)->eligibleTeachersFor(Auth::user());
        $completed = $period
            ? TeacherEvaluation::where('evaluation_period_id', $period->id)->where('user_id', Auth::id())->pluck('teacher_id')->all()
            : [];

        return view('evaluations.index', compact('period', 'teachers', 'completed'));
    }

    public function create(Teacher $teacher): View
    {
        abort_unless(Auth::user()->hasRole('alumno', 'apoderado'), 403);

        $period = $this->activePeriod();
        abort_if(! $period, 403, 'No hay periodo de evaluación activo.');
        abort_unless(app(EvaluationEligibilityService::class)->canEvaluate(Auth::user(), $teacher), 403, 'No puedes evaluar a este docente porque no está asignado a tu matrícula.');

        $type = $this->evaluatorType();
        $criteria = EvaluationCriterion::query()
            ->where('is_active', true)
            ->where('evaluator_type', $type)
            ->where(function ($query) use ($period) {
                $query->whereNull('evaluation_period_id')->orWhere('evaluation_period_id', $period->id);
            })
            ->orderBy('id')
            ->get();

        abort_if($criteria->isEmpty(), 403, 'No hay criterios configurados para este tipo de evaluador.');
        abort_if($this->alreadyEvaluated($period, $teacher), 403, 'Ya evaluaste a este docente en el periodo activo.');

        return view('evaluations.form', compact('period', 'teacher', 'criteria', 'type'));
    }

    public function store(Request $request, Teacher $teacher): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('alumno', 'apoderado'), 403);

        $period = $this->activePeriod();
        abort_if(! $period, 403, 'No hay periodo de evaluación activo.');
        abort_unless(app(EvaluationEligibilityService::class)->canEvaluate(Auth::user(), $teacher), 403, 'No puedes evaluar a este docente porque no está asignado a tu matrícula.');
        abort_if($this->alreadyEvaluated($period, $teacher), 403, 'Ya evaluaste a este docente en el periodo activo.');

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $type = $this->evaluatorType();
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

    private function activePeriod(): ?EvaluationPeriod
    {
        return EvaluationPeriod::where('status', 'activo')
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->latest('id')
            ->first();
    }

    private function evaluatorType(): string
    {
        return Auth::user()->hasRole('apoderado') ? 'apoderado' : 'alumno';
    }

    private function alreadyEvaluated(EvaluationPeriod $period, Teacher $teacher): bool
    {
        return TeacherEvaluation::where('evaluation_period_id', $period->id)
            ->where('teacher_id', $teacher->id)
            ->where('user_id', Auth::id())
            ->exists();
    }
}
