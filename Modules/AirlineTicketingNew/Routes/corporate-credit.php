<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Corporate\CorporateAgreementController;

Route::get('corporate/agreements', [CorporateAgreementController::class, 'index'])
    ->name('corporate.agreements.index')
    ->middleware('permission:airline_ticketing_new.corporate_agreements.view');

Route::post('corporate/agreements', [CorporateAgreementController::class, 'store'])
    ->name('corporate.agreements.store')
    ->middleware('permission:airline_ticketing_new.corporate_agreements.manage');
