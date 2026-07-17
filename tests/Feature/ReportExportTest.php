<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_reports_and_export_excel_and_pdf(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/reportes?report=students&q=ALU-001')
            ->assertOk()
            ->assertSee('Reportes')
            ->assertSee('ALU-001');

        $excel = $this->actingAs($admin)
            ->get('/reportes/export/excel?report=students&q=ALU-001')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertStringStartsWith('PK', $excel->getContent());
        $this->assertStringNotContainsString('<html>', $excel->getContent());

        $this->actingAs($admin)
            ->get('/reportes/export/pdf?report=students&q=ALU-001')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_teacher_reports_are_limited_to_own_evaluations(): void
    {
        $this->seed();

        $teacher = User::where('email', 'docente@school.com')->firstOrFail();

        $this->actingAs($teacher)
            ->get('/reportes?report=students')
            ->assertOk()
            ->assertSee('Mis evaluaciones')
            ->assertSee('Calificaciones recibidas')
            ->assertSee('Tipo evaluador')
            ->assertDontSee('Tablas filtrables para gestión y exportación.')
            ->assertDontSee('Alumnos</option>', false)
            ->assertDontSee('name="teacher_id"', false)
            ->assertDontSee('Docente</th>', false)
            ->assertDontSee('Año escolar');
    }

    public function test_secretary_can_access_reports_and_export(): void
    {
        $this->seed();

        $secretary = User::where('email', 'secretaria@school.com')->firstOrFail();

        $this->actingAs($secretary)
            ->get('/reportes?report=enrollments')
            ->assertOk()
            ->assertSee('Matrículas');

        $this->actingAs($secretary)
            ->get('/reportes/export/excel?report=enrollments')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
