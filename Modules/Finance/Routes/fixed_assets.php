<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FixedAssets\FixedAssetController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance')
    ->group(function () {
        Route::get('/fixed-asset/filter-options', [FixedAssetController::class, 'filterOptions'])
            ->name('finance.fixed-assets.filter-options');
        Route::resource('/fixed-asset', FixedAssetController::class)
            ->except(['show'])
            ->names('finance.fixed-assets');
    });
