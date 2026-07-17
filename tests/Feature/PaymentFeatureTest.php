<?php

namespace Tests\Feature;

use App\Models\PaymentConcept;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_payment_concepts(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/payment-concepts?q=2026')
            ->assertOk()
            ->assertSee('Conceptos de pago')
            ->assertSee('Matrícula 2026');
    }

    public function test_guardian_can_see_student_payments(): void
    {
        $this->seed();

        $guardian = User::where('email', 'apoderado@school.com')->firstOrFail();

        $this->actingAs($guardian)
            ->get('/pagos')
            ->assertOk()
            ->assertSee('Pagos')
            ->assertSee('Lucía Pérez Ramos')
            ->assertSee('Matrícula 2026')
            ->assertSee('Mensualidad Marzo')
            ->assertSee('S/ 250.00');
    }

    public function test_student_payment_uses_concept_amount_when_created(): void
    {
        $this->seed();

        $secretary = User::where('email', 'secretaria@school.com')->firstOrFail();
        $concept = PaymentConcept::where('type', 'mensualidad')->firstOrFail();
        $student = Student::create([
            'code' => 'ALU-999',
            'first_names' => 'Pago',
            'last_names' => 'Sin Monto Manual',
            'dni' => '79999990',
            'status' => 'activo',
        ]);

        $this->actingAs($secretary)
            ->post('/admin/student-payments', [
                'student_id' => $student->id,
                'payment_concept_id' => $concept->id,
                'amount_paid' => '',
                'status' => 'pendiente',
                'due_date' => '',
                'paid_at' => '',
                'payment_method' => '',
                'receipt_number' => '',
            ])
            ->assertRedirect('/admin/student-payments');

        $payment = StudentPayment::where('student_id', $student->id)
            ->where('payment_concept_id', $concept->id)
            ->firstOrFail();

        $this->assertSame((string) $concept->amount, (string) $payment->amount);
        $this->assertSame((string) $concept->amount, (string) $payment->original_amount);
        $this->assertSame((string) $concept->amount, (string) $payment->total_amount);
        $this->assertSame('0.00', (string) $payment->amount_paid);
        $this->assertTrue($payment->due_date->isSameDay($concept->due_date));
    }
}
