<?php

use App\Services\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:release-expired-reservations', function (OrderService $orderService) {
    $orderService->cleanupExpiredPendingOrders();

    $this->info('Expired pending payment reservations released successfully.');
})->purpose('Release expired pending payment reservations');
