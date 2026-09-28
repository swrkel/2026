<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone'],
    'prefix' => 'leasing',
], function () {
    Route::get('/', 'DashboardController@index')->name('leasing.dashboard.root');
    Route::get('dashboard', 'DashboardController@index')->name('leasing.dashboard');

    Route::get('collateral-types', 'LeaseAssetTypeController@index')->name('leasing.collateral-types.index');
    Route::get('collateral-types/create', 'LeaseAssetTypeController@create')->name('leasing.collateral-types.create');
    Route::post('collateral-types', 'LeaseAssetTypeController@store')->name('leasing.collateral-types.store');
    Route::get('collateral-types/{id}/edit', 'LeaseAssetTypeController@edit')->name('leasing.collateral-types.edit');
    Route::post('collateral-types/{id}', 'LeaseAssetTypeController@update')->name('leasing.collateral-types.update');

    Route::get('products', 'LeasingProductController@index')->name('leasing.products.index');
    Route::get('products/create', 'LeasingProductController@create')->name('leasing.products.create');
    Route::post('products', 'LeasingProductController@store')->name('leasing.products.store');
    Route::get('products/{id}/edit', 'LeasingProductController@edit')->name('leasing.products.edit');
    Route::post('products/{id}', 'LeasingProductController@update')->name('leasing.products.update');

    Route::get('lease_assets', 'LeaseAssetController@index')->name('leasing.lease_assets.index');
    Route::get('lease_assets/create', 'LeaseAssetController@create')->name('leasing.lease_assets.create');
    Route::post('lease_assets', 'LeaseAssetController@store')->name('leasing.lease_assets.store');
    Route::get('lease_assets/{id}', 'LeaseAssetController@show')->name('leasing.lease_assets.show');
    Route::get('lease_assets/{id}/edit', 'LeaseAssetController@edit')->name('leasing.lease_assets.edit');
    Route::post('lease_assets/{id}', 'LeaseAssetController@update')->name('leasing.lease_assets.update');

    Route::get('lease_contracts', 'LeaseContractController@index')->name('leasing.lease_contracts.index');
    Route::get('lease_contracts/create', 'LeaseContractController@create')->name('leasing.lease_contracts.create');
    Route::post('lease_contracts', 'LeaseContractController@store')->name('leasing.lease_contracts.store');
    Route::get('lease_contracts/{id}', 'LeaseContractController@show')->name('leasing.lease_contracts.show');
    Route::get('lease_contracts/{id}/edit', 'LeaseContractController@edit')->name('leasing.lease_contracts.edit');
    Route::post('lease_contracts/{id}', 'LeaseContractController@update')->name('leasing.lease_contracts.update');

    Route::get('applications', 'ApplicationController@index')->name('leasing.applications.index');
    Route::post('applications/calculate', 'ApplicationController@calculate')->name('leasing.applications.calculate');

    Route::get('payments', 'PaymentController@index')->name('leasing.payments.index');
    Route::post('payments/{id}/redeem', 'PaymentController@redeem')->name('leasing.payments.redeem');

    Route::get('restructures', 'RestructureController@index')->name('leasing.restructures.index');
    Route::post('restructures/{id}/renew', 'RestructureController@renew')->name('leasing.restructures.renew');

    Route::get('insurance', 'InsuranceController@index')->name('leasing.insurance.index');
    Route::post('insurance/{id}/mark', 'InsuranceController@mark')->name('leasing.insurance.mark');

    Route::get('asset', 'AssetController@index')->name('leasing.asset.index');
    Route::post('asset', 'AssetController@store')->name('leasing.asset.store');

    Route::get('reports', 'ReportController@index')->name('leasing.reports.index');
    Route::get('reports/export', 'ReportController@export')->name('leasing.reports.export');

    Route::get('settings', 'SettingController@index')->name('leasing.settings.index');
    Route::post('settings', 'SettingController@update')->name('leasing.settings.update');
});
