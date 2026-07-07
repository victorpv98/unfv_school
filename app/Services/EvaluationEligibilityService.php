<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EvaluationEligibilityService
{
    public function eligibleTeachersFor(User $user): Collection
    {
        if ($user->hasRole('administrador')) {
            return Teacher::where('status', 'activo')->orderBy('last_names')->get();
        }

        $enrollments = $this->activeEnrollmentsFor($user);

        if ($enrollments->isEmpty()) {
            return new Collection();
        }

        return Teacher::query()
            ->where('status', 'activo')
            ->whereHas('courses', function ($query) use ($enrollments) {
                $query->where(function ($subquery) use ($enrollments) {
                    foreach ($enrollments as $enrollment) {
                        $subquery->orWhere(function ($inner) use ($enrollment) {
                            $inner->where('teacher_assignments.grade_id', $enrollment->grade_id)
                                ->where('teacher_assignments.section', $enrollment->section)
                                ->where('teacher_assignments.academic_year_id', $enrollment->academic_year_id);
                        });
                    }
                });
            })
            ->orderBy('last_names')
            ->get();
    }

    public function canEvaluate(User $user, Teacher $teacher): bool
    {
        return $this->eligibleTeachersFor($user)->contains('id', $teacher->id);
    }

    private function activeEnrollmentsFor(User $user): Collection
    {
        $yearId = AcademicYear::where('status', 'activo')->orderByDesc('year')->value('id');

        if ($user->hasRole('alumno')) {
            $student = Student::where('user_id', $user->id)->first();

            return $student
                ? $student->enrollments()->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))->where('status', 'matriculado')->get()
                : new Collection();
        }

        if ($user->hasRole('apoderado')) {
            $guardian = Guardian::where('user_id', $user->id)->first();

            if (! $guardian) {
                return new Collection();
            }

            $studentIds = $guardian->students()->pluck('students.id');

            return Enrollment::whereIn('student_id', $studentIds)
                ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                ->where('status', 'matriculado')
                ->get();
        }

        return new Collection();
    }
}
