<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(Request $request, string $resource): View
    {
        $config = $this->config($resource);
        $this->authorizeResource($resource, false);
        $model = $this->model($config);
        $items = $model::query()
            ->with($config['with'] ?? [])
            ->when($request->filled('q'), fn ($query) => $this->search($query, $config, $request->string('q')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('resources.index', compact('resource', 'config', 'items'));
    }

    public function create(string $resource): View
    {
        $config = $this->config($resource);
        $this->authorizeResource($resource, true);
        return view('resources.form', [
            'resource' => $resource,
            'config' => $config,
            'item' => null,
            'options' => $this->options($config),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = $this->config($resource);
        $this->authorizeResource($resource, true);
        $item = $this->model($config)::create($this->payload($request, $config));
        $this->syncRelations($request, $config, $item);

        return redirect()->route('resources.index', $resource)->with('status', 'Registro creado correctamente.');
    }

    public function edit(string $resource, int $id): View
    {
        $config = $this->config($resource);
        $this->authorizeResource($resource, true);
        $item = $this->model($config)::with($config['with'] ?? [])->findOrFail($id);

        return view('resources.form', [
            'resource' => $resource,
            'config' => $config,
            'item' => $item,
            'options' => $this->options($config),
        ]);
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $this->authorizeResource($resource, true);
        $item = $this->model($config)::findOrFail($id);
        $item->update($this->payload($request, $config, $item));
        $this->syncRelations($request, $config, $item);

        return redirect()->route('resources.index', $resource)->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $this->authorizeResource($resource, true);
        $this->model($config)::findOrFail($id)->delete();

        return back()->with('status', 'Registro eliminado correctamente.');
    }

    private function config(string $resource): array
    {
        abort_unless(array_key_exists($resource, config('school.resources')), 404);
        return config("school.resources.$resource");
    }

    private function model(array $config): string
    {
        return $config['model'];
    }

    private function payload(Request $request, array $config, ?Model $item = null): array
    {
        $rules = collect($config['fields'])->mapWithKeys(function (array $field, string $name) use ($item) {
            if (($field['type'] ?? 'text') === 'multiselect') {
                return [
                    $name => $field['rules'] ?? ['nullable', 'array'],
                    "{$name}.*" => ['integer', 'exists:'.$field['table'].',id'],
                ];
            }

            $rule = $field['rules'] ?? ['nullable'];
            if (($field['type'] ?? 'text') === 'boolean') {
                $rule = ['nullable', 'boolean'];
            }

            return [$name => $rule];
        })->all();

        $data = $request->validate($rules);

        if (($config['model'] ?? null) === \App\Models\Student::class && ! empty($data['guardian_ids'])) {
            $relationships = \App\Models\Guardian::whereIn('id', $data['guardian_ids'])
                ->pluck('relationship')
                ->filter()
                ->map(fn (string $relationship) => trim($relationship))
                ->all();

            if (count($relationships) !== count(array_unique($relationships))) {
                throw ValidationException::withMessages([
                    'guardian_ids' => 'Un alumno solo puede tener un apoderado por parentesco: Madre, Padre y Apoderado.',
                ]);
            }
        }

        foreach ($config['fields'] as $name => $field) {
            if (($field['type'] ?? 'text') === 'boolean') {
                $data[$name] = $request->boolean($name);
            }

            if (($field['type'] ?? 'text') === 'multiselect') {
                unset($data[$name]);
            }
        }

        $this->applyOptionalUserAccess($data, $config);

        foreach ($config['fields'] as $name => $field) {
            if (($field['virtual'] ?? false) === true) {
                unset($data[$name]);
            }
        }

        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        if (($config['model'] ?? null) === \App\Models\User::class && $item === null && ! array_key_exists('password', $data)) {
            $data['password'] = 'password';
        }

        if (($config['model'] ?? null) === \App\Models\Enrollment::class && array_key_exists('academic_year', $data)) {
            $academicYear = \App\Models\AcademicYear::firstOrCreate(
                ['year' => (int) $data['academic_year']],
                ['status' => 'activo']
            );

            $data['academic_year_id'] = $academicYear->id;
            unset($data['academic_year']);
        }

        if (($config['model'] ?? null) === \App\Models\Enrollment::class && array_key_exists('section_name', $data)) {
            $grade = \App\Models\Grade::findOrFail($data['grade_id']);
            if ((int) $grade->level_id !== (int) $data['level_id']) {
                throw ValidationException::withMessages([
                    'grade_id' => 'El grado seleccionado no pertenece al nivel indicado.',
                ]);
            }

            $data['section'] = $data['section_name'];
            unset($data['section_name']);
        }

        if (($config['model'] ?? null) === \App\Models\PaymentConcept::class) {
            if (($data['type'] ?? null) === 'matricula') {
                $data['month'] = null;
            }

            if (($data['type'] ?? null) === 'mensualidad' && blank($data['month'] ?? null)) {
                throw ValidationException::withMessages([
                    'month' => 'Seleccione el mes de la mensualidad.',
                ]);
            }

            $exists = \App\Models\PaymentConcept::query()
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('type', $data['type'])
                ->when(
                    blank($data['month'] ?? null),
                    fn ($query) => $query->whereNull('month'),
                    fn ($query) => $query->where('month', $data['month'])
                )
                ->when($item, fn ($query) => $query->whereKeyNot($item->getKey()))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'Ya existe un concepto de pago con el mismo año, tipo y mes.',
                ]);
            }
        }

	        if (($config['model'] ?? null) === \App\Models\StudentPayment::class) {
	            $concept = \App\Models\PaymentConcept::findOrFail($data['payment_concept_id']);
	            $sameConcept = $item && (int) $item->payment_concept_id === (int) $concept->id;
	            $baseAmount = $sameConcept ? (float) ($item->amount ?: $concept->amount) : (float) $concept->amount;
	            $lateFeeAmount = $sameConcept ? (float) ($item->late_fee_amount ?? 0) : 0.0;

	            $data['amount'] = $baseAmount;
	            $data['original_amount'] = $sameConcept ? (float) ($item->original_amount ?: $baseAmount) : $baseAmount;
	            $data['late_fee_amount'] = $lateFeeAmount;
	            $data['total_amount'] = round((float) $data['original_amount'] + $lateFeeAmount, 2);
	            $data['amount_paid'] = blank($data['amount_paid'] ?? null) ? 0 : $data['amount_paid'];
	            $data['due_date'] = $data['due_date'] ?? $concept->due_date?->toDateString();

	            if (($data['status'] ?? null) === 'pagado') {
	                $data['amount_paid'] = $data['amount_paid'] ?: ($data['total_amount'] ?? $data['amount']);
                $data['paid_at'] = $data['paid_at'] ?? now()->toDateString();
                $data['registered_by'] = Auth::id();
                $data['exam_blocked'] = false;
                $data['exam_unblocked_at'] = now();
            }

            if (($data['status'] ?? null) === 'parcial' && (float) ($data['amount_paid'] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Ingrese el monto pagado para un pago parcial.',
                ]);
            }
        }

        return $data;
    }

    private function applyOptionalUserAccess(array &$data, array $config): void
    {
        $roleByModel = [
            \App\Models\Student::class => 'alumno',
            \App\Models\Guardian::class => 'apoderado',
            \App\Models\Teacher::class => 'docente',
        ];

        $model = $config['model'] ?? null;

        if (! isset($roleByModel[$model]) || empty($data['create_user']) || ! empty($data['user_id'])) {
            return;
        }

        $fallbackName = trim(($data['first_names'] ?? '').' '.($data['last_names'] ?? ''));
        $user = app(\App\Services\UserAccessService::class)->createOptionalAccess([
            'create_user' => true,
            'user_email' => $data['user_email'] ?? null,
            'user_password' => $data['user_password'] ?? null,
        ], $roleByModel[$model], $fallbackName);

        $data['user_id'] = $user?->id;
    }

    private function syncRelations(Request $request, array $config, Model $item): void
    {
        foreach ($config['fields'] as $name => $field) {
            if (($field['type'] ?? 'text') !== 'multiselect') {
                continue;
            }

            $ids = collect($request->input($name, []))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $syncData = $ids->mapWithKeys(function (int $id, int $index) use ($field, $item) {
                $pivot = [];

                if (($field['pivot_relationship'] ?? null) === 'from_self') {
                    $pivot['relationship'] = $item->relationship;
                }

                if (($field['pivot_relationship'] ?? null) === 'from_related') {
                    $related = $field['model']::find($id);
                    $pivot['relationship'] = $related?->relationship;
                }

                if (array_key_exists('is_primary', $field)) {
                    $pivot['is_primary'] = $index === 0;
                }

                return [$id => $pivot];
            })->all();

        $item->{$field['relation']}()->sync($syncData);
        }

        if ($item instanceof \App\Models\StudentPayment) {
            app(\App\Services\PaymentLateFeeService::class)->reconcilePayment($item->fresh());
        }
    }

    private function search($query, array $config, string $term): void
    {
        $columns = $config['search'] ?? $config['columns'];
        $virtualColumns = array_keys($config['column_values'] ?? []);
        $operator = $query->getModel()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query->where(function ($subquery) use ($columns, $term, $virtualColumns, $operator) {
            foreach ($columns as $column) {
                if (! Str::contains($column, '.') && ! in_array($column, $virtualColumns, true)) {
                    $subquery->orWhere($column, $operator, "%{$term}%");
                }
            }
        });
    }

    private function options(array $config): array
    {
        $options = [];

        foreach ($config['fields'] as $name => $field) {
            if (in_array(($field['type'] ?? null), ['select', 'multiselect'], true) && isset($field['options'])) {
                $options[$name] = $field['options'];
            }

            if (in_array(($field['type'] ?? null), ['select', 'multiselect'], true) && isset($field['model'])) {
                $display = $field['display'] ?? 'name';
                $query = $field['model']::query();

                if (isset($field['with'])) {
                    $query->with($field['with']);
                }

                $options[$name] = $query
                    ->orderBy('id')
                    ->get()
                    ->mapWithKeys(fn ($item) => [$item->id => is_callable($display) ? $display($item) : $item->{$display}])
                    ->all();
            }
        }

        return $options;
    }

    private function authorizeResource(string $resource, bool $write): void
    {
        $user = Auth::user();

        if ($user->hasRole('administrador')) {
            return;
        }

        if ($user->hasRole('secretaria') && in_array($resource, ['students', 'guardians', 'teachers', 'enrollments', 'student-payments'], true)) {
            return;
        }

        abort(403, 'No tienes permiso para acceder a este módulo.');
    }
}
