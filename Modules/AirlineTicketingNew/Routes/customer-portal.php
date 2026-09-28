<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Portal\CustomerPortalController;

Route::middleware('auth:atn_portal')->prefix('portal')->as('portal.')->group(function (): void {
    Route::get('dashboard', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
});
