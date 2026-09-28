<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix(config('bankinginsurance.route_prefix', 'banking/insurance'))->as('banking-insurance.')->namespace('Modules\\BankingInsurance\\Http\\Controllers')->group(function () {
    Route::get('/', 'DashboardController@index')->name('dashboard');
    Route::get('/dashboard/data', 'DashboardController@data')->name('dashboard.data');

    Route::resource('products', 'ProductController');
    Route::resource('policies', 'PolicyController');
    Route::get('policies/{policy}/print', 'PolicyController@print')->name('policies.print');
    Route::post('policies/{policy}/activate', 'PolicyController@activate')->name('policies.activate');
    Route::post('policies/{policy}/cancel', 'PolicyController@cancel')->name('policies.cancel');

    Route::resource('claims', 'ClaimController');
    Route::post('claims/{claim}/approve', 'ClaimController@approve')->name('claims.approve');
    Route::post('claims/{claim}/reject', 'ClaimController@reject')->name('claims.reject');
    Route::post('claims/{claim}/settle', 'ClaimController@settle')->name('claims.settle');

    Route::resource('premiums', 'PremiumPaymentController')->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('premiums/{premium}/receipt', 'PremiumPaymentController@receipt')->name('premiums.receipt');

    Route::get('reports/policies', 'ReportController@policies')->name('reports.policies');
    Route::get('reports/claims', 'ReportController@claims')->name('reports.claims');
    Route::get('reports/premiums', 'ReportController@premiums')->name('reports.premiums');

    Route::get('settings', 'SettingController@index')->name('settings.index');
    Route::post('settings', 'SettingController@store')->name('settings.store');
});
