<?php

use App\Jobs\GenerateMonthlyExport;
use App\Jobs\ProcessDunning;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Billing (Module 12) : rattrapage quotidien des abonnements (essais,
// retards, période de grâce, suspension).
Schedule::command('billing:check-due')->daily();

// RGPD : purge différée des comptes console supprimés logiquement.
Schedule::command('admin-users:purge --days=30')->dailyAt('03:00');

// Billing : dunning — relances 1/3/7j puis suspension à 14j (P1)
Schedule::job(new ProcessDunning)->dailyAt('02:00');

// Exports compta — mensuel le 1er à 04:00 (P1)
Schedule::job(new GenerateMonthlyExport)->monthlyOn(1, '04:00');
