<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Transactions\QuotationController;
use Modules\AirlineTicketingNew\Http\Controllers\Transactions\ReservationController;
use Modules\AirlineTicketingNew\Http\Controllers\Transactions\TransactionLookupController;

Route::middleware('permission:airline_ticketing_new.quotations.view')->group(function (): void {
    Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
});

Route::middleware('permission:airline_ticketing_new.quotations.create')->group(function (): void {
    Route::get('quotations-create', [QuotationController::class, 'create'])->name('quotations.create');
    Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
});

Route::middleware('permission:airline_ticketing_new.reservations.view')->group(function (): void {
    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
});

Route::post('quotations/{quotation}/convert', [ReservationController::class, 'convert'])
    ->name('quotations.convert')
    ->middleware('permission:airline_ticketing_new.reservations.create');

Route::post('reservations/{reservation}/status', [ReservationController::class, 'changeStatus'])
    ->name('reservations.status')
    ->middleware('permission:airline_ticketing_new.reservations.status');

Route::prefix('lookups')->as('lookups.')->group(function (): void {
    Route::get('passengers', [TransactionLookupController::class, 'passengers'])->name('passengers');
    Route::get('corporate-customers', [TransactionLookupController::class, 'corporateCustomers'])->name('corporate-customers');
});
