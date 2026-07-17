<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_enrollments_index(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/enrollments')
            ->assertOk()
            ->assertSee('Matrículas')
            ->assertSee('2026');
    }

    public function test_enrollment_form_uses_simple_section_and_no_guardian_select(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/enrollments/create')
            ->assertOk()
            ->assertSee('name="section_name"', false)
            ->assertSee('value="A"', false)
            ->assertSee('value="B"', false)
            ->assertSee('value="C"', false)
            ->assertDontSee('name="guardian_id"', false)
            ->assertDontSee('Inicial - 3 años A');
    }
}
