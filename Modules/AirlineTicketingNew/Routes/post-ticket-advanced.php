<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Controls\OperationalExceptionController;
use Modules\AirlineTicketingNew\Http\Controllers\PostTicket\ReissueQuoteController;

Route::post('tickets/{ticket}/reissue-quote', [ReissueQuoteController::class, 'store'])
    ->name('reissue-quotes.store')
    ->middleware('permission:airline_ticketing_new.reissue_quotes.create');

Route::get('controls/exceptions', [OperationalExceptionController::class, 'index'])
    ->name('controls.exceptions.index')
    ->middleware('permission:airline_ticketing_new.exceptions.view');

Route::post('controls/exceptions/{exception}/resolve', [OperationalExceptionController::class, 'resolve'])
    ->name('controls.exceptions.resolve')
    ->middleware('permission:airline_ticketing_new.exceptions.resolve');
