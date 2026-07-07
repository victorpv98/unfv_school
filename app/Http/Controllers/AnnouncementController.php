<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $announcements = Announcement::query()
            ->with(['creator', 'recipients'])
            ->when($user->hasRole('alumno', 'apoderado'), function ($query) use ($user) {
                $query->whereHas('recipients', fn ($recipient) => $recipient->where('user_id', $user->id))
                    ->where('status', 'published');
            })
            ->when($user->hasRole('docente'), function ($query) use ($user) {
                $query->where(function ($subquery) use ($user) {
                    $subquery->where('created_by', $user->id)
                        ->orWhereHas('recipients', fn ($recipient) => $recipient->where('user_id', $user->id));
                });
            })
            ->latest('id')
            ->paginate(12);

        return view('announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()->hasRole('administrador', 'secretaria', 'docente'), 403);

        return view('announcements.form', $this->formData(null));
    }

    public function store(Request $request, AnnouncementService $service): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('administrador', 'secretaria', 'docente'), 403);

        $data = $this->validated($request);
        $service->validateTeacherCanTarget(Auth::user(), $data);
        $announcement = $service->create($data, Auth::user(), $request->input('action') === 'publish');

        return redirect()->route('announcements.show', $announcement)->with('status', 'Comunicado creado correctamente.');
    }

    public function show(Announcement $announcement): View
    {
        $this->authorizeView($announcement);

        return view('announcements.show', [
            'announcement' => $announcement->load(['creator', 'recipients.user', 'recipients.student', 'recipients.guardian']),
        ]);
    }

    public function edit(Announcement $announcement): View
    {
        abort_unless($this->canManage($announcement), 403);

        return view('announcements.form', $this->formData($announcement));
    }

    public function update(Request $request, Announcement $announcement, AnnouncementService $service): RedirectResponse
    {
        abort_unless($this->canManage($announcement), 403);

        $data = $this->validated($request);
        $service->validateTeacherCanTarget(Auth::user(), $data);
        $announcement->update($data + ['updated_by' => Auth::id()]);

        if ($request->input('action') === 'publish') {
            $service->publish($announcement);
        }

        return redirect()->route('announcements.show', $announcement)->with('status', 'Comunicado actualizado correctamente.');
    }

    public function publish(Announcement $announcement, AnnouncementService $service): RedirectResponse
    {
        abort_unless($this->canManage($announcement), 403);
        $service->publish($announcement);

        return back()->with('status', 'Comunicado publicado correctamente.');
    }

    public function archive(Announcement $announcement, AnnouncementService $service): RedirectResponse
    {
        abort_unless($this->canManage($announcement), 403);
        $service->archive($announcement);

        return back()->with('status', 'Comunicado archivado correctamente.');
    }

    public function recipients(Announcement $announcement): View
    {
        abort_unless($this->canManage($announcement), 403);

        return view('announcements.recipients', [
            'announcement' => $announcement->load(['recipients.user', 'recipients.student', 'recipients.guardian']),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', 'in:general,academico,pago,mora,examen,urgente,otro'],
            'priority' => ['required', 'in:baja,normal,alta,urgente'],
            'target_type' => ['required', 'in:student,guardian,teacher,classroom,level,grade,all_students,all_guardians,all_users'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'level_id' => ['nullable', 'exists:levels,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'section' => ['nullable', 'in:A,B,C'],
            'student_id' => ['nullable', 'exists:students,id'],
            'guardian_id' => ['nullable', 'exists:guardians,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
            'status' => ['required', 'in:draft,published,archived,cancelled'],
        ]);

        $required = match ($data['target_type']) {
            'student' => ['student_id'],
            'guardian' => ['guardian_id'],
            'teacher' => ['teacher_id'],
            'classroom' => ['academic_year_id', 'level_id', 'grade_id', 'section'],
            'grade' => ['academic_year_id', 'level_id', 'grade_id'],
            'level' => ['academic_year_id', 'level_id'],
            default => [],
        };

        foreach ($required as $field) {
            if (blank($data[$field] ?? null)) {
                throw ValidationException::withMessages([$field => 'Este campo es obligatorio para el destinatario seleccionado.']);
            }
        }

        return $data;
    }

    private function formData(?Announcement $announcement): array
    {
        return [
            'announcement' => $announcement,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'levels' => Level::orderBy('id')->get(),
            'grades' => Grade::with('level')->orderBy('id')->get(),
            'students' => Student::orderBy('last_names')->get(),
            'guardians' => Guardian::orderBy('last_names')->get(),
            'teachers' => Teacher::orderBy('last_names')->get(),
        ];
    }

    private function authorizeView(Announcement $announcement): void
    {
        $user = Auth::user();

        abort_unless(
            $user->hasRole('administrador', 'secretaria')
            || $announcement->created_by === $user->id
            || $announcement->recipients()->where('user_id', $user->id)->exists(),
            403
        );
    }

    private function canManage(Announcement $announcement): bool
    {
        $user = Auth::user();

        return $user->hasRole('administrador', 'secretaria') || ($user->hasRole('docente') && $announcement->created_by === $user->id);
    }
}
