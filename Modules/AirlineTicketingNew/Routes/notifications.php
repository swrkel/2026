<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Notifications\NotificationQueueController;

Route::get('notifications/queue', [NotificationQueueController::class, 'index'])
    ->name('notifications.queue')
    ->middleware('permission:airline_ticketing_new.notifications.view');

Route::post('notifications/queue/dispatch', [NotificationQueueController::class, 'dispatch'])
    ->name('notifications.dispatch')
    ->middleware('permission:airline_ticketing_new.notifications.dispatch');
