<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Corporate\RestaurantCorporateAccountController;

Route::middleware(['web', 'auth', 'restaurantnew.scope'])
    ->prefix('restaurant-new/corporate')
    ->as('restaurant-new.corporate.')
    ->group(function () {
        Route::get('/', [RestaurantCorporateAccountController::class, 'index'])->name('index');
        Route::get('/create', [RestaurantCorporateAccountController::class, 'create'])->name('create');
        Route::post('/', [RestaurantCorporateAccountController::class, 'store'])->name('store');
        Route::get('/invoices', [RestaurantCorporateAccountController::class, 'invoices'])->name('invoices');
        Route::get('/statement', [RestaurantCorporateAccountController::class, 'statement'])->name('statement');
    });
