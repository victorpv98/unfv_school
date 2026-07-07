<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AnnouncementRecipientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentWizardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\LateFeeController;
use App\Http\Controllers\LateFeeSettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\TeacherAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware(CheckRole::class.':administrador,secretaria')->prefix('admin')->group(function () {
        Route::redirect('/usuarios', '/admin/users')->name('admin.users.index');
        Route::redirect('/alumnos', '/admin/students')->name('admin.students.index');
        Route::redirect('/apoderados', '/admin/guardians')->name('admin.guardians.index');
        Route::redirect('/docentes', '/admin/teachers')->name('admin.teachers.index');
        Route::redirect('/matriculas', '/admin/enrollments')->name('admin.enrollments.index');
        Route::redirect('/pagos', '/admin/student-payments')->name('admin.payments.index');
        Route::redirect('/configuracion/anios', '/admin/academic-years')->name('admin.settings.years');
        Route::redirect('/configuracion/niveles', '/admin/levels')->name('admin.settings.levels');
        Route::redirect('/configuracion/grados', '/admin/grades')->name('admin.settings.grades');
        Route::redirect('/configuracion/cursos', '/admin/courses')->name('admin.settings.courses');
        Route::redirect('/configuracion/conceptos-pago', '/admin/payment-concepts')->name('admin.settings.payment-concepts');
        Route::get('/configuracion/moras', fn () => redirect()->route('late-fee-settings.index'))->name('admin.settings.late-fees');
        Route::redirect('/configuracion/periodos-evaluacion', '/admin/evaluation-periods')->name('admin.settings.evaluation-periods');
        Route::redirect('/configuracion/criterios-evaluacion', '/admin/evaluation-criteria')->name('admin.settings.evaluation-criteria');

        Route::get('/configuracion/moras/listado', [LateFeeSettingController::class, 'index'])->name('late-fee-settings.index');
        Route::get('/configuracion/moras/crear', [LateFeeSettingController::class, 'create'])->name('late-fee-settings.create');
        Route::post('/configuracion/moras', [LateFeeSettingController::class, 'store'])->name('late-fee-settings.store');
        Route::get('/configuracion/moras/{lateFeeSetting}/editar', [LateFeeSettingController::class, 'edit'])->name('late-fee-settings.edit');
        Route::put('/configuracion/moras/{lateFeeSetting}', [LateFeeSettingController::class, 'update'])->name('late-fee-settings.update');
        Route::get('/pagos/moras', [LateFeeController::class, 'index'])->name('late-fees.payments.index');
        Route::post('/pagos/aplicar-moras', [LateFeeController::class, 'apply'])->name('late-fees.apply');

        Route::get('/{resource}', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('/{resource}/create', [ResourceController::class, 'create'])->name('resources.create');
        Route::post('/{resource}', [ResourceController::class, 'store'])->name('resources.store');
        Route::get('/{resource}/{id}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
        Route::put('/{resource}/{id}', [ResourceController::class, 'update'])->name('resources.update');
        Route::delete('/{resource}/{id}', [ResourceController::class, 'destroy'])->name('resources.destroy');
    });

    Route::middleware(CheckRole::class.':administrador,secretaria')->group(function () {
        Route::redirect('/secretaria/alumnos', '/admin/students')->name('secretary.students.index');
        Route::redirect('/secretaria/apoderados', '/admin/guardians')->name('secretary.guardians.index');
        Route::redirect('/secretaria/matriculas', '/admin/enrollments')->name('secretary.enrollments.index');
        Route::redirect('/secretaria/matriculas/nueva', '/matriculas/nueva')->name('secretary.enrollments.create');
        Route::redirect('/secretaria/pagos', '/pagos')->name('secretary.payments.index');
        Route::get('/matriculas/nueva', [EnrollmentWizardController::class, 'create'])->name('enrollments.wizard.create');
        Route::post('/matriculas/nueva', [EnrollmentWizardController::class, 'store'])->name('enrollments.wizard.store');
        Route::get('/asignacion-docente', [TeacherAssignmentController::class, 'index'])->name('teacher-assignments.index');
        Route::post('/asignacion-docente', [TeacherAssignmentController::class, 'store'])->name('teacher-assignments.store');
    });

    Route::middleware(CheckRole::class.':alumno,apoderado,administrador')->group(function () {
        Route::get('/evaluaciones', [EvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('/evaluaciones/{teacher}', [EvaluationController::class, 'create'])->name('evaluations.create');
        Route::post('/evaluaciones/{teacher}', [EvaluationController::class, 'store'])->name('evaluations.store');
    });

    Route::middleware(CheckRole::class.':apoderado,administrador,secretaria')
        ->get('/pagos', [PaymentController::class, 'index'])
        ->name('payments.index');

    Route::middleware(CheckRole::class.':administrador,secretaria,docente,alumno,apoderado')->group(function () {
        Route::get('/comunicados', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/comunicados/crear', [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/comunicados', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/comunicados/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
        Route::get('/comunicados/{announcement}/editar', [AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('/comunicados/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::post('/comunicados/{announcement}/publicar', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::post('/comunicados/{announcement}/archivar', [AnnouncementController::class, 'archive'])->name('announcements.archive');
        Route::post('/comunicados/{announcement}/leer', [AnnouncementRecipientController::class, 'read'])->name('announcements.read');
        Route::get('/comunicados/{announcement}/destinatarios', [AnnouncementController::class, 'recipients'])->name('announcements.recipients');
    });

    Route::redirect('/alumno/calificar-docentes', '/evaluaciones')->middleware(CheckRole::class.':alumno')->name('student.evaluations.index');
    Route::redirect('/apoderado/calificar-docentes', '/evaluaciones')->middleware(CheckRole::class.':apoderado')->name('guardian.evaluations.index');
    Route::redirect('/apoderado/pagos', '/pagos')->middleware(CheckRole::class.':apoderado')->name('guardian.payments.index');
    Route::redirect('/apoderado/hijos', '/pagos')->middleware(CheckRole::class.':apoderado')->name('guardian.children.index');
    Route::redirect('/docente/evaluaciones', '/reportes')->middleware(CheckRole::class.':docente')->name('teacher.evaluations.index');

    Route::middleware(CheckRole::class.':administrador,docente')
        ->get('/reportes', ReportController::class)
        ->name('reports.index');
});
