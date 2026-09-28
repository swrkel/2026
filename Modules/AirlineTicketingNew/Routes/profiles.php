<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\CorporateContactController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\CorporateCustomerController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\DocumentExpiryReportController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\PassengerController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\PassengerDocumentController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\PassengerEmergencyContactController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\PassengerLoyaltyAccountController;
use Modules\AirlineTicketingNew\Http\Controllers\Passengers\PassengerVisaController;

Route::resource('passengers', PassengerController::class)
    ->except(['show','destroy'])
    ->middleware('permission:airline_ticketing_new.passengers.manage');

Route::resource('corporate-customers', CorporateCustomerController::class)
    ->except(['show','destroy'])
    ->middleware('permission:airline_ticketing_new.corporate_customers.manage');

Route::prefix('passengers/{passenger}')->as('passengers.')->group(function (): void {
    Route::get('documents', [PassengerDocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [PassengerDocumentController::class, 'store'])->name('documents.store');
    Route::get('visas', [PassengerVisaController::class, 'index'])->name('visas.index');
    Route::post('visas', [PassengerVisaController::class, 'store'])->name('visas.store');
    Route::get('loyalty-accounts', [PassengerLoyaltyAccountController::class, 'index'])->name('loyalty-accounts.index');
    Route::post('loyalty-accounts', [PassengerLoyaltyAccountController::class, 'store'])->name('loyalty-accounts.store');
    Route::get('emergency-contacts', [PassengerEmergencyContactController::class, 'index'])->name('emergency-contacts.index');
    Route::post('emergency-contacts', [PassengerEmergencyContactController::class, 'store'])->name('emergency-contacts.store');
});

Route::prefix('corporate-customers/{corporateCustomer}')->as('corporate-customers.')->group(function (): void {
    Route::get('contacts', [CorporateContactController::class, 'index'])->name('contacts.index');
    Route::post('contacts', [CorporateContactController::class, 'store'])->name('contacts.store');
});

Route::get('reports/document-expiry', [DocumentExpiryReportController::class, 'index'])
    ->name('reports.document-expiry')
    ->middleware('permission:airline_ticketing_new.reports.document_expiry');
