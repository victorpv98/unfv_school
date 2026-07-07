<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\LateFeeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LateFeeSettingController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->hasRole('administrador'), 403);

        return view('late-fees.settings.index', [
            'settings' => LateFeeSetting::with('academicYear')->latest('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        abort_unless(Auth::user()->hasRole('administrador'), 403);

        return view('late-fees.settings.form', [
            'setting' => null,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('administrador'), 403);

        $data = $this->validated($request);
        $this->ensureSingleActiveSetting($data);

        LateFeeSetting::create($data + [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('late-fee-settings.index')->with('status', 'Configuración de mora creada correctamente.');
    }

    public function edit(LateFeeSetting $lateFeeSetting): View
    {
        abort_unless(Auth::user()->hasRole('administrador'), 403);

        return view('late-fees.settings.form', [
            'setting' => $lateFeeSetting,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
        ]);
    }

    public function update(Request $request, LateFeeSetting $lateFeeSetting): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('administrador'), 403);

        $data = $this->validated($request);
        $this->ensureSingleActiveSetting($data, $lateFeeSetting);

        $lateFeeSetting->update($data + ['updated_by' => Auth::id()]);

        return redirect()->route('late-fee-settings.index')->with('status', 'Configuración de mora actualizada correctamente.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'name' => ['required', 'max:255'],
            'grace_days' => ['required', 'integer', 'min:0'],
            'late_fee_percentage' => ['required', 'numeric', 'min:0'],
            'blocks_exam_right' => ['nullable', 'boolean'],
            'auto_generate_notice' => ['nullable', 'boolean'],
            'notice_title' => ['required', 'max:255'],
            'notice_message' => ['nullable', 'string'],
            'status' => ['required', 'in:activo,inactivo'],
        ]) + [
            'blocks_exam_right' => $request->boolean('blocks_exam_right'),
            'auto_generate_notice' => $request->boolean('auto_generate_notice'),
        ];
    }

    private function ensureSingleActiveSetting(array $data, ?LateFeeSetting $current = null): void
    {
        if (($data['status'] ?? null) !== 'activo') {
            return;
        }

        $exists = LateFeeSetting::query()
            ->where('status', 'activo')
            ->where('academic_year_id', $data['academic_year_id'] ?? null)
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Ya existe una configuración activa para este año escolar.',
            ]);
        }
    }
}
