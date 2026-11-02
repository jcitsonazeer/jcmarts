<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:reconcile-razorpay')->everyFiveMinutes()->withoutOverlapping();
Schedule::call(function () {
    app(\App\Services\OrderService::class)->cleanupExpiredPendingOrders();
})->everyMinute()->name('cleanup-expired-pending-orders')->withoutOverlapping();
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

