<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnouncementService
{
    public function create(array $data, ?User $creator = null, bool $publish = false): Announcement
    {
        return DB::transaction(function () use ($data, $creator, $publish) {
            $creator ??= Auth::user();
            $payload = $data;
            $payload['created_by'] = $creator?->id;
            $payload['updated_by'] = $creator?->id;

            if ($publish) {
                $payload['status'] = 'published';
                $payload['publish_at'] = $payload['publish_at'] ?? now();
            }

            $announcement = Announcement::create($payload);

            if ($announcement->status === 'published') {
                $this->syncRecipients($announcement);
            }

            return $announcement;
        });
    }

    public function publish(Announcement $announcement): Announcement
    {
        $announcement->update([
            'status' => 'published',
            'publish_at' => $announcement->publish_at ?? now(),
            'updated_by' => Auth::id(),
        ]);

        $this->syncRecipients($announcement);

        return $announcement;
    }

    public function archive(Announcement $announcement): Announcement
    {
        $announcement->update([
            'status' => 'archived',
            'updated_by' => Auth::id(),
        ]);

        return $announcement;
    }

    public function syncRecipients(Announcement $announcement): int
    {
        $recipients = $this->resolveRecipients($announcement);
        $created = 0;

        foreach ($recipients as $recipient) {
            if (empty($recipient['user_id'])) {
                continue;
            }

            $model = AnnouncementRecipient::firstOrCreate([
                'announcement_id' => $announcement->id,
                'user_id' => $recipient['user_id'],
            ], [
                'student_id' => $recipient['student_id'] ?? null,
                'guardian_id' => $recipient['guardian_id'] ?? null,
                'delivered_at' => now(),
            ]);

            if ($model->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    public function resolveRecipients(Announcement $announcement): Collection
    {
        return (match ($announcement->target_type) {
            'student' => $this->studentRecipients(Student::find($announcement->student_id)),
            'guardian' => $this->guardianRecipients(Guardian::find($announcement->guardian_id)),
            'teacher' => $this->teacherRecipients(Teacher::find($announcement->teacher_id)),
            'classroom' => $this->classroomRecipients($announcement),
            'level' => $this->levelRecipients($announcement),
            'grade' => $this->gradeRecipients($announcement),
            'all_students' => $this->allStudentsRecipients(),
            'all_guardians' => $this->allGuardiansRecipients(),
            'all_users' => $this->userRecipients(User::where('is_active', true)->get()),
            default => collect(),
        })->unique('user_id')->values();
    }

    public function validateTeacherCanTarget(User $user, array $data): void
    {
        if (! $user->hasRole('docente')) {
            return;
        }

        if (! in_array($data['target_type'] ?? null, ['classroom', 'grade', 'student', 'guardian'], true)) {
            throw ValidationException::withMessages([
                'target_type' => 'El docente solo puede enviar comunicados a sus aulas, alumnos o apoderados relacionados.',
            ]);
        }

        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            throw ValidationException::withMessages(['target_type' => 'No se encontró el docente asociado al usuario.']);
        }

        $hasAssignment = DB::table('teacher_assignments')
            ->where('teacher_id', $teacher->id)
            ->when($data['academic_year_id'] ?? null, fn ($query, $yearId) => $query->where('academic_year_id', $yearId))
            ->when($data['grade_id'] ?? null, fn ($query, $gradeId) => $query->where('grade_id', $gradeId))
            ->when($data['section'] ?? null, fn ($query, $section) => $query->where('section', $section))
            ->exists();

        if (! $hasAssignment) {
            throw ValidationException::withMessages([
                'target_type' => 'El docente solo puede enviar comunicados a aulas asignadas.',
            ]);
        }
    }

    private function studentRecipients(?Student $student): Collection
    {
        if (! $student) {
            return collect();
        }

        if ($student->user_id) {
            return collect([['user_id' => $student->user_id, 'student_id' => $student->id]]);
        }

        $guardian = $student->guardians()->wherePivot('is_primary', true)->first() ?? $student->guardians()->first();

        return $this->guardianRecipients($guardian)->map(fn (array $recipient) => [
            ...$recipient,
            'student_id' => $student->id,
        ]);
    }

    private function guardianRecipients(?Guardian $guardian): Collection
    {
        return $guardian?->user_id
            ? collect([['user_id' => $guardian->user_id, 'guardian_id' => $guardian->id]])
            : collect();
    }

    private function teacherRecipients(?Teacher $teacher): Collection
    {
        return $teacher?->user_id
            ? collect([['user_id' => $teacher->user_id]])
            : collect();
    }

    private function classroomRecipients(Announcement $announcement): Collection
    {
        return $this->enrollmentRecipients(
            Enrollment::query()
                ->where('academic_year_id', $announcement->academic_year_id)
                ->where('level_id', $announcement->level_id)
                ->where('grade_id', $announcement->grade_id)
                ->where('section', $announcement->section)
                ->where('status', 'matriculado')
                ->get()
        );
    }

    private function levelRecipients(Announcement $announcement): Collection
    {
        return $this->enrollmentRecipients(
            Enrollment::query()
                ->where('academic_year_id', $announcement->academic_year_id)
                ->where('level_id', $announcement->level_id)
                ->where('status', 'matriculado')
                ->get()
        );
    }

    private function gradeRecipients(Announcement $announcement): Collection
    {
        return $this->enrollmentRecipients(
            Enrollment::query()
                ->where('academic_year_id', $announcement->academic_year_id)
                ->where('level_id', $announcement->level_id)
                ->where('grade_id', $announcement->grade_id)
                ->where('status', 'matriculado')
                ->get()
        );
    }

    private function allStudentsRecipients(): Collection
    {
        return Student::with('guardians')->get()->flatMap(fn (Student $student) => $this->studentRecipients($student));
    }

    private function allGuardiansRecipients(): Collection
    {
        return Guardian::whereNotNull('user_id')->get()->flatMap(fn (Guardian $guardian) => $this->guardianRecipients($guardian));
    }

    private function userRecipients(Collection $users): Collection
    {
        return $users->map(fn (User $user) => ['user_id' => $user->id]);
    }

    private function enrollmentRecipients(Collection $enrollments): Collection
    {
        return $enrollments->flatMap(function (Enrollment $enrollment) {
            $student = $enrollment->student;
            $studentRecipients = $this->studentRecipients($student);
            $guardianRecipients = $student?->guardians?->flatMap(fn (Guardian $guardian) => $this->guardianRecipients($guardian)) ?? collect();

            return $studentRecipients->merge($guardianRecipients);
        });
    }
}
