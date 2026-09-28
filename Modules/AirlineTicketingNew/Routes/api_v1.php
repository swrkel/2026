<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Api\V1\ReservationApiController;
use Modules\AirlineTicketingNew\Http\Controllers\Api\V1\TicketApiController;

Route::prefix('api/airline-ticketing-new/v1')
    ->middleware('atn.api.key')
    ->group(function (): void {
        Route::get('reservations', [ReservationApiController::class, 'index']);
        Route::get('reservations/{reservation}', [ReservationApiController::class, 'show']);
        Route::get('tickets', [TicketApiController::class, 'index']);
    });
