<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge des entrées du journal d'audit au-delà de leur durée de conservation (AuditLog::RETENTION_MONTHS).
Schedule::command('model:prune')->daily();
