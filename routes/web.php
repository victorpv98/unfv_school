<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentWizardController;
use App\Http\Controllers\EvaluationController;
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

    Route::middleware(CheckRole::class.':administrador,director,secretaria')->prefix('admin')->group(function () {
        Route::get('/{resource}', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('/{resource}/create', [ResourceController::class, 'create'])->name('resources.create');
        Route::post('/{resource}', [ResourceController::class, 'store'])->name('resources.store');
        Route::get('/{resource}/{id}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
        Route::put('/{resource}/{id}', [ResourceController::class, 'update'])->name('resources.update');
        Route::delete('/{resource}/{id}', [ResourceController::class, 'destroy'])->name('resources.destroy');
    });

    Route::middleware(CheckRole::class.':administrador,secretaria')->group(function () {
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

    Route::middleware(CheckRole::class.':apoderado,administrador')
        ->get('/pagos', [PaymentController::class, 'index'])
        ->name('payments.index');

    Route::middleware(CheckRole::class.':administrador,director,profesor')
        ->get('/reportes', ReportController::class)
        ->name('reports.index');
});
