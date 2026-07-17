<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\LateFeeSetting;
use App\Models\StudentPayment;
use App\Models\User;
use App\Services\PaymentLateFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LateFeeAndAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_late_fee_setting(): void
    {
        $this->seed();

        LateFeeSetting::query()->delete();
        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/configuracion/moras', [
                'academic_year_id' => 1,
                'name' => 'Mora test',
                'grace_days' => 5,
                'late_fee_percentage' => 5,
                'blocks_exam_right' => '1',
                'auto_generate_notice' => '1',
                'notice_title' => 'Aviso de mora',
                'notice_message' => 'Mensaje',
                'status' => 'activo',
            ])
            ->assertRedirect('/admin/configuracion/moras/listado');

        $this->assertDatabaseHas('late_fee_settings', [
            'name' => 'Mora test',
            'late_fee_percentage' => 5,
            'status' => 'activo',
        ]);
    }

    public function test_only_one_active_late_fee_setting_is_allowed_per_year(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/configuracion/moras', [
                'academic_year_id' => 1,
                'name' => 'Duplicada',
                'grace_days' => 3,
                'late_fee_percentage' => 4,
                'notice_title' => 'Aviso',
                'status' => 'activo',
            ])
            ->assertSessionHasErrors('academic_year_id');
    }

    public function test_late_fee_is_applied_after_grace_days_and_not_duplicated(): void
    {
        $this->seed();

        $payment = StudentPayment::firstOrFail();
        $payment->update([
            'due_date' => '2026-03-01',
            'amount' => 200,
            'original_amount' => 200,
            'total_amount' => 200,
            'status' => 'pendiente',
        ]);

        $service = app(PaymentLateFeeService::class);
        $first = $service->applyToPayment($payment->fresh(), Carbon::parse('2026-03-07'));
        $second = $service->applyToPayment($payment->fresh(), Carbon::parse('2026-03-08'));

        $payment->refresh();

        $this->assertTrue($first['applied']);
        $this->assertFalse($second['applied']);
        $this->assertSame('vencido', $payment->status);
        $this->assertSame('10.00', (string) $payment->late_fee_amount);
        $this->assertSame('210.00', (string) $payment->total_amount);
        $this->assertTrue($payment->exam_blocked);
    }

    public function test_late_fee_does_not_apply_to_paid_payments(): void
    {
        $this->seed();

        $payment = StudentPayment::firstOrFail();
        $payment->update([
            'due_date' => '2026-03-01',
            'status' => 'pagado',
            'amount_paid' => $payment->amount,
        ]);

        $result = app(PaymentLateFeeService::class)->applyToPayment($payment->fresh(), Carbon::parse('2026-04-01'));

        $this->assertFalse($result['applied']);
        $this->assertSame('0.00', (string) $payment->fresh()->late_fee_amount);
    }

    public function test_late_fee_generates_automatic_announcement_once(): void
    {
        $this->seed();

        $payment = StudentPayment::firstOrFail();
        $payment->update([
            'due_date' => '2026-03-01',
            'status' => 'pendiente',
            'notice_generated_at' => null,
        ]);

        $service = app(PaymentLateFeeService::class);
        $service->applyToPayment($payment->fresh(), Carbon::parse('2026-04-01'));
        $service->applyToPayment($payment->fresh(), Carbon::parse('2026-04-02'));

        $this->assertSame(1, Announcement::where('student_payment_id', $payment->id)->where('type', 'mora')->count());
        $this->assertSame(1, AnnouncementRecipient::whereHas('announcement', fn ($query) => $query->where('student_payment_id', $payment->id))->count());
    }

    public function test_admin_can_create_announcement_for_all_users(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@school.com')->firstOrFail();

        $this->actingAs($admin)
            ->post('/comunicados', [
                'title' => 'Comunicado general',
                'message' => 'Mensaje para todos.',
                'type' => 'general',
                'priority' => 'normal',
                'target_type' => 'all_users',
                'status' => 'published',
                'action' => 'publish',
            ])
            ->assertRedirect();

        $announcement = Announcement::where('title', 'Comunicado general')->firstOrFail();
        $this->assertSame(User::where('is_active', true)->count(), $announcement->recipients()->count());
    }

    public function test_classroom_announcement_requires_section_and_valid_values(): void
    {
        $this->seed();

        $secretary = User::where('email', 'secretaria@school.com')->firstOrFail();

        $this->actingAs($secretary)
            ->post('/comunicados', [
                'title' => 'Aula',
                'message' => 'Mensaje de aula.',
                'type' => 'academico',
                'priority' => 'normal',
                'target_type' => 'classroom',
                'academic_year_id' => 1,
                'level_id' => 2,
                'grade_id' => 4,
                'section' => 'D',
                'status' => 'published',
            ])
            ->assertSessionHasErrors('section');
    }

    public function test_recipient_can_mark_announcement_as_read(): void
    {
        $this->seed();

        $user = User::where('email', 'alumno@school.com')->firstOrFail();
        $announcement = Announcement::where('status', 'published')->firstOrFail();

        $this->actingAs($user)
            ->post("/comunicados/{$announcement->id}/leer")
            ->assertRedirect();

        $this->assertNotNull(
            AnnouncementRecipient::where('announcement_id', $announcement->id)
                ->where('user_id', $user->id)
                ->firstOrFail()
                ->read_at
        );
    }
}
