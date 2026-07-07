<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\PaymentLateFeeService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:apply-late-fees', function () {
    $result = app(PaymentLateFeeService::class)->applyAll();

    $this->info('Proceso de moras completado.');
    $this->line("Pagos revisados: {$result['reviewed']}");
    $this->line("Moras aplicadas: {$result['applied']}");
    $this->line("Comunicados generados: {$result['notices']}");

    if (! empty($result['errors'])) {
        $this->warn('Errores:');
        foreach ($result['errors'] as $error) {
            $this->line("- {$error}");
        }
    }
})->purpose('Apply late fees to overdue student payments');

Schedule::command('payments:apply-late-fees')->daily();
