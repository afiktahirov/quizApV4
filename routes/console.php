<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ödəniş sistemi: bank redirect-i baş vermədiyi halları serverdən-serverə yoxlayır.
Schedule::command('payments:sync-pending')->everyFiveMinutes();

// Yadda saxlanılan kartla avtomatik abunəlik yenilənməsi.
Schedule::command('subscriptions:auto-renew')->dailyAt('03:00');
