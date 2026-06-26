<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_payment_concepts(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/payment-concepts?q=2026')
            ->assertOk()
            ->assertSee('Conceptos de pago')
            ->assertSee('Matrícula 2026');
    }

    public function test_guardian_can_see_student_payments(): void
    {
        $this->seed();

        $guardian = User::where('email', 'apoderado@school.test')->firstOrFail();

        $this->actingAs($guardian)
            ->get('/pagos')
            ->assertOk()
            ->assertSee('Pagos')
            ->assertSee('Lucía Pérez Ramos')
            ->assertSee('Matrícula 2026')
            ->assertSee('Mensualidad Marzo')
            ->assertSee('S/ 250.00');
    }
}
