<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Analytics\AnalyticsController;

Route::get('analytics', [AnalyticsController::class, 'index'])
    ->name('analytics.index')
    ->middleware('permission:airline_ticketing_new.analytics.view');
