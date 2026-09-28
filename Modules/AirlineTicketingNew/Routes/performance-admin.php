<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Admin\DiagnosticsController;

Route::get('admin/diagnostics', [DiagnosticsController::class, 'index'])
    ->name('admin.diagnostics.index')
    ->middleware('permission:airline_ticketing_new.diagnostics.view');
