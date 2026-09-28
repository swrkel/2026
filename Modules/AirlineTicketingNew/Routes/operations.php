<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Operations\OperationalQueueController;

Route::get('operations/queue', [OperationalQueueController::class, 'index'])
    ->name('operations.queue')
    ->middleware('permission:airline_ticketing_new.operations.view');

Route::post('operations/queue/rebuild', [OperationalQueueController::class, 'rebuild'])
    ->name('operations.queue.rebuild')
    ->middleware('permission:airline_ticketing_new.operations.manage');
