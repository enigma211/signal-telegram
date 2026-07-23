<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// قطع دسترسی VIPهای منقضی‌شده — هر روز ساعت 00:15
Schedule::command('vip:expire')->dailyAt('00:15');

// اعلان انقضای نزدیک VIP — هر روز ساعت 10:00
Schedule::command('vip:remind')->dailyAt('10:00');

// تأیید خودکار هش تراکنش روی زنجیره — هر ۲ دقیقه
Schedule::command('payments:verify-chain')->everyTwoMinutes();
