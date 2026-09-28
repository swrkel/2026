<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone'],
    'prefix' => 'deposits',
], function () {
    Route::get('/', 'DashboardController@index')->name('deposits.dashboard.root');
    Route::get('dashboard', 'DashboardController@index')->name('deposits.dashboard');

    Route::get('products', 'DepositProductController@index')->name('deposits.products.index');
    Route::get('products/create', 'DepositProductController@create')->name('deposits.products.create');
    Route::post('products', 'DepositProductController@store')->name('deposits.products.store');
    Route::get('products/{id}/edit', 'DepositProductController@edit')->name('deposits.products.edit');
    Route::post('products/{id}', 'DepositProductController@update')->name('deposits.products.update');
    Route::post('products/{id}/delete', 'DepositProductController@destroy')->name('deposits.products.delete');

    Route::get('accounts', 'DepositAccountController@index')->name('deposits.accounts.index');
    Route::get('accounts/create', 'DepositAccountController@create')->name('deposits.accounts.create');
    Route::post('accounts', 'DepositAccountController@store')->name('deposits.accounts.store');
    Route::get('accounts/{id}', 'DepositAccountController@show')->name('deposits.accounts.show');
    Route::get('accounts/{id}/edit', 'DepositAccountController@edit')->name('deposits.accounts.edit');
    Route::post('accounts/{id}', 'DepositAccountController@update')->name('deposits.accounts.update');
    Route::post('accounts/{id}/delete', 'DepositAccountController@destroy')->name('deposits.accounts.delete');

    Route::get('transactions', 'DepositTransactionController@index')->name('deposits.transactions.index');
    Route::get('transactions/create', 'DepositTransactionController@create')->name('deposits.transactions.create');
    Route::post('transactions', 'DepositTransactionController@store')->name('deposits.transactions.store');

    Route::get('interest', 'DepositInterestController@index')->name('deposits.interest.index');
    Route::post('interest/post', 'DepositInterestController@post')->name('deposits.interest.post');

    Route::get('maturity/due', 'DepositMaturityController@due')->name('deposits.maturity.due');
    Route::post('maturity/{id}/close', 'DepositMaturityController@close')->name('deposits.maturity.close');
    Route::post('maturity/{id}/renew', 'DepositMaturityController@renew')->name('deposits.maturity.renew');

    Route::get('accounts/{id}/certificate', 'DepositCertificateController@show')->name('deposits.certificates.show');
    Route::get('accounts/{id}/statement', 'DepositAccountController@statement')->name('deposits.accounts.statement');

    Route::get('reports', 'DepositReportController@index')->name('deposits.reports.index');

    Route::get('settings', 'DepositSettingController@index')->name('deposits.settings.index');
    Route::post('settings', 'DepositSettingController@update')->name('deposits.settings.update');
});
