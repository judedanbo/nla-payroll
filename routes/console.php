<?php

use App\Jobs\DetectDiscrepanciesJob;
use App\Jobs\EscalateOverdueDiscrepanciesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule discrepancy detection to run daily at 2:00 AM
Schedule::job(new DetectDiscrepanciesJob)
    ->daily()
    ->at('02:00')
    ->name('detect-discrepancies')
    ->onOneServer()
    ->withoutOverlapping();

// Schedule overdue discrepancy escalation to run daily at 8:00 AM
Schedule::job(new EscalateOverdueDiscrepanciesJob)
    ->daily()
    ->at('08:00')
    ->name('escalate-overdue-discrepancies')
    ->onOneServer()
    ->withoutOverlapping();
