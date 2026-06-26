<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationPeriod;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\PaymentConcept;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Teacher;
use App\Models\User;

$status = ['activo' => 'Activo', 'inactivo' => 'Inactivo'];
$relationships = ['Madre' => 'Madre', 'Padre' => 'Padre', 'Apoderado' => 'Apoderado'];
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

return [
    'resources' => [
        'users' => [
            'label' => 'Usuarios',
            'model' => User::class,
            'columns' => ['name', 'email', 'role', 'is_active'],
            'fields' => [
                'role' => ['label' => 'Rol', 'type' => 'select', 'options' => User::ROLES, 'rules' => ['required', 'in:administrador,director,secretaria,profesor,alumno,apoderado']],
                'name' => ['label' => 'Nombre', 'rules' => ['required', 'max:255']],
                'email' => ['label' => 'Correo', 'type' => 'email', 'rules' => ['required', 'email', 'max:255']],
                'password' => ['label' => 'Contraseña', 'type' => 'password', 'rules' => ['nullable', 'min:6']],
                'is_active' => ['label' => 'Activo', 'type' => 'boolean', 'default' => true],
            ],
        ],
        'students' => [
            'label' => 'Alumnos',
            'model' => Student::class,
            'with' => ['guardians'],
            'columns' => ['code', 'first_names', 'last_names', 'dni', 'guardians', 'status'],
            'search' => ['code', 'first_names', 'last_names', 'dni', 'status'],
            'column_labels' => ['guardians' => 'Apoderados'],
            'column_values' => [
                'guardians' => fn (Student $student) => $student->guardians
                    ->map(fn (Guardian $guardian) => trim("{$guardian->first_names} {$guardian->last_names}".($guardian->pivot->relationship ? " ({$guardian->pivot->relationship})" : '').($guardian->pivot->is_primary ? ' - principal' : '')))
                    ->join(', ') ?: 'Sin apoderados',
            ],
            'fields' => [
                'user_id' => ['label' => 'Usuario', 'type' => 'select', 'model' => User::class, 'display' => 'email', 'rules' => ['nullable', 'exists:users,id']],
                'create_user' => ['label' => 'Crear usuario de acceso', 'type' => 'boolean', 'default' => false, 'virtual' => true],
                'user_email' => ['label' => 'Correo para acceso', 'type' => 'email', 'virtual' => true, 'rules' => ['nullable', 'email', 'max:255']],
                'user_password' => ['label' => 'Contraseña temporal', 'type' => 'password', 'virtual' => true, 'rules' => ['nullable', 'min:6']],
                'code' => ['label' => 'Código', 'rules' => ['required', 'max:255']],
                'first_names' => ['label' => 'Nombres', 'rules' => ['required', 'max:255']],
                'last_names' => ['label' => 'Apellidos', 'rules' => ['required', 'max:255']],
                'dni' => ['label' => 'DNI', 'rules' => ['required', 'max:20']],
                'birth_date' => ['label' => 'Fecha de nacimiento', 'type' => 'date'],
                'gender' => ['label' => 'Género', 'type' => 'select', 'options' => ['Masculino' => 'Masculino', 'Femenino' => 'Femenino']],
                'address' => ['label' => 'Dirección'],
                'guardian_ids' => [
                    'label' => 'Apoderados asociados',
                    'type' => 'multiselect',
                    'relation' => 'guardians',
                    'model' => Guardian::class,
                    'table' => 'guardians',
                    'display' => fn (Guardian $g) => "{$g->first_names} {$g->last_names}".($g->relationship ? " - {$g->relationship}" : ''),
                    'pivot_relationship' => 'from_related',
                    'is_primary' => true,
                    'rules' => ['nullable', 'array'],
                ],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => ['activo' => 'Activo', 'retirado' => 'Retirado', 'inactivo' => 'Inactivo']],
            ],
        ],
        'guardians' => [
            'label' => 'Apoderados',
            'model' => Guardian::class,
            'with' => ['students'],
            'columns' => ['first_names', 'last_names', 'dni', 'phone', 'relationship', 'students', 'status'],
            'search' => ['first_names', 'last_names', 'dni', 'phone', 'relationship', 'status'],
            'column_labels' => ['students' => 'Alumnos asociados'],
            'column_values' => [
                'students' => fn (Guardian $guardian) => $guardian->students
                    ->map(fn (Student $student) => trim("{$student->code} - {$student->first_names} {$student->last_names}".($student->pivot->is_primary ? ' - principal' : '')))
                    ->join(', ') ?: 'Sin alumnos',
            ],
            'fields' => [
                'user_id' => ['label' => 'Usuario', 'type' => 'select', 'model' => User::class, 'display' => 'email', 'rules' => ['nullable', 'exists:users,id']],
                'create_user' => ['label' => 'Crear usuario de acceso', 'type' => 'boolean', 'default' => false, 'virtual' => true],
                'user_email' => ['label' => 'Correo para acceso', 'type' => 'email', 'virtual' => true, 'rules' => ['nullable', 'email', 'max:255']],
                'user_password' => ['label' => 'Contraseña temporal', 'type' => 'password', 'virtual' => true, 'rules' => ['nullable', 'min:6']],
                'first_names' => ['label' => 'Nombres', 'rules' => ['required', 'max:255']],
                'last_names' => ['label' => 'Apellidos', 'rules' => ['required', 'max:255']],
                'dni' => ['label' => 'DNI', 'rules' => ['required', 'max:20']],
                'phone' => ['label' => 'Teléfono'],
                'email' => ['label' => 'Correo', 'type' => 'email'],
                'address' => ['label' => 'Dirección'],
                'relationship' => ['label' => 'Parentesco', 'type' => 'select', 'options' => $relationships, 'rules' => ['required', 'in:Madre,Padre,Apoderado']],
                'student_ids' => [
                    'label' => 'Alumnos asociados',
                    'type' => 'multiselect',
                    'relation' => 'students',
                    'model' => Student::class,
                    'table' => 'students',
                    'display' => fn (Student $s) => "{$s->code} - {$s->first_names} {$s->last_names}",
                    'pivot_relationship' => 'from_self',
                    'rules' => ['nullable', 'array'],
                ],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => $status],
            ],
        ],
        'teachers' => [
            'label' => 'Profesores',
            'model' => Teacher::class,
            'columns' => ['code', 'first_names', 'last_names', 'dni', 'specialty', 'status'],
            'fields' => [
                'user_id' => ['label' => 'Usuario', 'type' => 'select', 'model' => User::class, 'display' => 'email', 'rules' => ['nullable', 'exists:users,id']],
                'create_user' => ['label' => 'Crear usuario de acceso', 'type' => 'boolean', 'default' => false, 'virtual' => true],
                'user_email' => ['label' => 'Correo para acceso', 'type' => 'email', 'virtual' => true, 'rules' => ['nullable', 'email', 'max:255']],
                'user_password' => ['label' => 'Contraseña temporal', 'type' => 'password', 'virtual' => true, 'rules' => ['nullable', 'min:6']],
                'code' => ['label' => 'Código', 'rules' => ['required', 'max:255']],
                'first_names' => ['label' => 'Nombres', 'rules' => ['required', 'max:255']],
                'last_names' => ['label' => 'Apellidos', 'rules' => ['required', 'max:255']],
                'dni' => ['label' => 'DNI', 'rules' => ['required', 'max:20']],
                'phone' => ['label' => 'Teléfono'],
                'email' => ['label' => 'Correo', 'type' => 'email'],
                'specialty' => ['label' => 'Especialidad'],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => $status],
            ],
        ],
        'courses' => [
            'label' => 'Cursos',
            'model' => Course::class,
            'columns' => ['code', 'name', 'level_id', 'grade_id', 'status'],
            'fields' => [
                'level_id' => ['label' => 'Nivel', 'type' => 'select', 'model' => Level::class, 'display' => 'name', 'rules' => ['required', 'exists:levels,id']],
                'grade_id' => ['label' => 'Grado', 'type' => 'select', 'model' => Grade::class, 'with' => ['level'], 'display' => fn (Grade $grade) => "{$grade->level->name} - {$grade->name}", 'rules' => ['required', 'exists:grades,id']],
                'code' => ['label' => 'Código', 'rules' => ['required', 'max:255']],
                'name' => ['label' => 'Nombre', 'rules' => ['required', 'max:255']],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => $status],
            ],
        ],
        'enrollments' => [
            'label' => 'Matrículas',
            'model' => Enrollment::class,
            'with' => ['student', 'academicYear', 'level', 'grade', 'section'],
            'columns' => ['student_id', 'academic_year_id', 'level_id', 'grade_id', 'section_id', 'status'],
            'column_labels' => [
                'student_id' => 'Alumno',
                'academic_year_id' => 'Año académico',
                'level_id' => 'Nivel',
                'grade_id' => 'Grado',
                'section_id' => 'Sección',
            ],
            'column_values' => [
                'student_id' => fn (Enrollment $enrollment) => $enrollment->student ? "{$enrollment->student->code} - {$enrollment->student->first_names} {$enrollment->student->last_names}" : '',
                'academic_year_id' => fn (Enrollment $enrollment) => $enrollment->academicYear?->year,
                'level_id' => fn (Enrollment $enrollment) => $enrollment->level?->name,
                'grade_id' => fn (Enrollment $enrollment) => $enrollment->grade?->name,
                'section_id' => fn (Enrollment $enrollment) => $enrollment->section?->name,
            ],
            'fields' => [
                'student_id' => ['label' => 'Alumno', 'type' => 'select', 'model' => Student::class, 'display' => fn ($s) => "{$s->code} - {$s->first_names} {$s->last_names}", 'rules' => ['required', 'exists:students,id']],
                'academic_year' => ['label' => 'Año académico', 'type' => 'number', 'value' => fn (Enrollment $enrollment) => $enrollment->academicYear?->year, 'rules' => ['required', 'integer', 'min:2000', 'max:2100']],
                'level_id' => ['label' => 'Nivel', 'type' => 'select', 'model' => Level::class, 'display' => 'name', 'rules' => ['required', 'exists:levels,id']],
                'grade_id' => ['label' => 'Grado', 'type' => 'select', 'model' => Grade::class, 'with' => ['level'], 'display' => fn (Grade $grade) => "{$grade->level->name} - {$grade->name}", 'rules' => ['required', 'exists:grades,id']],
                'section_name' => ['label' => 'Sección', 'type' => 'select', 'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C'], 'value' => fn (Enrollment $enrollment) => $enrollment->section?->name, 'rules' => ['required', 'in:A,B,C']],
                'enrolled_at' => ['label' => 'Fecha de matrícula', 'type' => 'date', 'rules' => ['required', 'date']],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => ['pendiente' => 'Pendiente', 'matriculado' => 'Matriculado', 'observado' => 'Observado', 'anulado' => 'Anulado', 'retirado' => 'Retirado']],
                'observations' => ['label' => 'Observaciones', 'type' => 'textarea'],
            ],
        ],
        'payment-concepts' => [
            'label' => 'Conceptos de pago',
            'model' => PaymentConcept::class,
            'with' => ['academicYear'],
            'columns' => ['academic_year_id', 'type', 'name', 'month', 'amount', 'due_date', 'status'],
            'column_labels' => [
                'academic_year_id' => 'Año académico',
                'type' => 'Tipo',
                'month' => 'Mes',
                'amount' => 'Monto',
                'due_date' => 'Vencimiento',
            ],
            'column_values' => [
                'academic_year_id' => fn (PaymentConcept $concept) => $concept->academicYear?->year,
                'type' => fn (PaymentConcept $concept) => ['matricula' => 'Matrícula', 'mensualidad' => 'Mensualidad'][$concept->type] ?? $concept->type,
                'month' => fn (PaymentConcept $concept) => $concept->month ? ($months[$concept->month] ?? $concept->month) : '-',
                'amount' => fn (PaymentConcept $concept) => 'S/ '.number_format((float) $concept->amount, 2),
                'due_date' => fn (PaymentConcept $concept) => $concept->due_date?->format('d/m/Y') ?? '-',
            ],
            'fields' => [
                'academic_year_id' => ['label' => 'Año académico', 'type' => 'select', 'model' => AcademicYear::class, 'display' => 'year', 'rules' => ['required', 'exists:academic_years,id']],
                'type' => ['label' => 'Tipo', 'type' => 'select', 'options' => ['matricula' => 'Matrícula', 'mensualidad' => 'Mensualidad'], 'rules' => ['required', 'in:matricula,mensualidad']],
                'name' => ['label' => 'Nombre', 'rules' => ['required', 'max:255']],
                'month' => ['label' => 'Mes', 'type' => 'select', 'options' => $months, 'rules' => ['nullable', 'integer', 'between:3,12']],
                'amount' => ['label' => 'Monto', 'type' => 'number', 'step' => '0.01', 'rules' => ['required', 'numeric', 'min:0']],
                'due_date' => ['label' => 'Fecha de vencimiento', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => $status],
            ],
        ],
        'student-payments' => [
            'label' => 'Pagos de alumnos',
            'model' => StudentPayment::class,
            'with' => ['student', 'paymentConcept'],
            'columns' => ['student_id', 'payment_concept_id', 'amount', 'amount_paid', 'status', 'due_date', 'paid_at', 'receipt_number'],
            'column_labels' => [
                'student_id' => 'Alumno',
                'payment_concept_id' => 'Concepto',
                'amount' => 'Monto',
                'amount_paid' => 'Pagado',
                'due_date' => 'Vencimiento',
                'paid_at' => 'Fecha pago',
                'receipt_number' => 'Recibo',
            ],
            'column_values' => [
                'student_id' => fn (StudentPayment $payment) => $payment->student ? "{$payment->student->code} - {$payment->student->first_names} {$payment->student->last_names}" : '',
                'payment_concept_id' => fn (StudentPayment $payment) => $payment->paymentConcept?->name,
                'amount' => fn (StudentPayment $payment) => 'S/ '.number_format((float) $payment->amount, 2),
                'amount_paid' => fn (StudentPayment $payment) => 'S/ '.number_format((float) $payment->amount_paid, 2),
                'due_date' => fn (StudentPayment $payment) => $payment->due_date?->format('d/m/Y') ?? '-',
                'paid_at' => fn (StudentPayment $payment) => $payment->paid_at?->format('d/m/Y') ?? '-',
            ],
            'fields' => [
                'student_id' => ['label' => 'Alumno', 'type' => 'select', 'model' => Student::class, 'display' => fn (Student $student) => "{$student->code} - {$student->first_names} {$student->last_names}", 'rules' => ['required', 'exists:students,id']],
                'payment_concept_id' => ['label' => 'Concepto', 'type' => 'select', 'model' => PaymentConcept::class, 'display' => 'name', 'rules' => ['required', 'exists:payment_concepts,id']],
                'amount' => ['label' => 'Monto', 'type' => 'number', 'step' => '0.01', 'rules' => ['required', 'numeric', 'min:0']],
                'amount_paid' => ['label' => 'Monto pagado', 'type' => 'number', 'step' => '0.01', 'rules' => ['required', 'numeric', 'min:0']],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => ['pendiente' => 'Pendiente', 'parcial' => 'Parcial', 'pagado' => 'Pagado', 'vencido' => 'Vencido', 'anulado' => 'Anulado'], 'rules' => ['required', 'in:pendiente,parcial,pagado,vencido,anulado']],
                'due_date' => ['label' => 'Vencimiento', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'paid_at' => ['label' => 'Fecha de pago', 'type' => 'date', 'rules' => ['nullable', 'date']],
                'payment_method' => ['label' => 'Método de pago'],
                'receipt_number' => ['label' => 'Número de recibo'],
                'observations' => ['label' => 'Observaciones', 'type' => 'textarea'],
            ],
        ],
        'evaluation-periods' => [
            'label' => 'Periodos de evaluación',
            'model' => EvaluationPeriod::class,
            'columns' => ['name', 'starts_at', 'ends_at', 'status'],
            'fields' => [
                'name' => ['label' => 'Nombre', 'rules' => ['required', 'max:255']],
                'starts_at' => ['label' => 'Inicio', 'type' => 'date', 'rules' => ['required', 'date']],
                'ends_at' => ['label' => 'Fin', 'type' => 'date', 'rules' => ['required', 'date']],
                'status' => ['label' => 'Estado', 'type' => 'select', 'options' => ['activo' => 'Activo', 'cerrado' => 'Cerrado']],
            ],
        ],
        'evaluation-criteria' => [
            'label' => 'Criterios de evaluación',
            'model' => EvaluationCriterion::class,
            'columns' => ['evaluator_type', 'name', 'is_active'],
            'fields' => [
                'evaluation_period_id' => ['label' => 'Periodo', 'type' => 'select', 'model' => EvaluationPeriod::class, 'display' => 'name', 'rules' => ['nullable', 'exists:evaluation_periods,id']],
                'evaluator_type' => ['label' => 'Tipo evaluador', 'type' => 'select', 'options' => ['alumno' => 'Alumno', 'apoderado' => 'Apoderado'], 'rules' => ['required']],
                'name' => ['label' => 'Criterio', 'rules' => ['required', 'max:255']],
                'description' => ['label' => 'Descripción', 'type' => 'textarea'],
                'is_active' => ['label' => 'Activo', 'type' => 'boolean', 'default' => true],
            ],
        ],
    ],
];
