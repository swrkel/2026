<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone'],
    'prefix' => 'pawning',
], function () {
    Route::get('/', 'DashboardController@index')->name('pawning.dashboard.root');
    Route::get('dashboard', 'DashboardController@index')->name('pawning.dashboard');

    Route::get('collateral-types', 'CollateralTypeController@index')->name('pawning.collateral-types.index');
    Route::get('collateral-types/create', 'CollateralTypeController@create')->name('pawning.collateral-types.create');
    Route::post('collateral-types', 'CollateralTypeController@store')->name('pawning.collateral-types.store');
    Route::get('collateral-types/{id}/edit', 'CollateralTypeController@edit')->name('pawning.collateral-types.edit');
    Route::post('collateral-types/{id}', 'CollateralTypeController@update')->name('pawning.collateral-types.update');

    Route::get('products', 'PawningProductController@index')->name('pawning.products.index');
    Route::get('products/create', 'PawningProductController@create')->name('pawning.products.create');
    Route::post('products', 'PawningProductController@store')->name('pawning.products.store');
    Route::get('products/{id}/edit', 'PawningProductController@edit')->name('pawning.products.edit');
    Route::post('products/{id}', 'PawningProductController@update')->name('pawning.products.update');

    Route::get('articles', 'ArticleController@index')->name('pawning.articles.index');
    Route::get('articles/create', 'ArticleController@create')->name('pawning.articles.create');
    Route::post('articles', 'ArticleController@store')->name('pawning.articles.store');
    Route::get('articles/{id}', 'ArticleController@show')->name('pawning.articles.show');
    Route::get('articles/{id}/edit', 'ArticleController@edit')->name('pawning.articles.edit');
    Route::post('articles/{id}', 'ArticleController@update')->name('pawning.articles.update');

    Route::get('pledges', 'PledgeController@index')->name('pawning.pledges.index');
    Route::get('pledges/create', 'PledgeController@create')->name('pawning.pledges.create');
    Route::post('pledges', 'PledgeController@store')->name('pawning.pledges.store');
    Route::get('pledges/{id}', 'PledgeController@show')->name('pawning.pledges.show');
    Route::get('pledges/{id}/edit', 'PledgeController@edit')->name('pawning.pledges.edit');
    Route::post('pledges/{id}', 'PledgeController@update')->name('pawning.pledges.update');

    Route::get('valuations', 'ValuationController@index')->name('pawning.valuations.index');
    Route::post('valuations/calculate', 'ValuationController@calculate')->name('pawning.valuations.calculate');

    Route::get('redemptions', 'RedemptionController@index')->name('pawning.redemptions.index');
    Route::post('redemptions/{id}/redeem', 'RedemptionController@redeem')->name('pawning.redemptions.redeem');

    Route::get('renewals', 'RenewalController@index')->name('pawning.renewals.index');
    Route::post('renewals/{id}/renew', 'RenewalController@renew')->name('pawning.renewals.renew');

    Route::get('auction', 'AuctionController@index')->name('pawning.auction.index');
    Route::post('auction/{id}/mark', 'AuctionController@mark')->name('pawning.auction.mark');

    Route::get('vault', 'VaultController@index')->name('pawning.vault.index');
    Route::post('vault', 'VaultController@store')->name('pawning.vault.store');

    Route::get('reports', 'ReportController@index')->name('pawning.reports.index');
    Route::get('reports/export', 'ReportController@export')->name('pawning.reports.export');

    Route::get('settings', 'SettingController@index')->name('pawning.settings.index');
    Route::post('settings', 'SettingController@update')->name('pawning.settings.update');
});
