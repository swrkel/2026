<?php

use Illuminate\Support\Facades\Route;
use Modules\ReportsCustomized\Http\Controllers\ReportsCustomizedController;
use Modules\ReportsCustomized\Http\Controllers\ReportsCustomizedSettingsController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'])
    ->group(function () {
        Route::get(
            'reportscustomized/list-statements',
            [ReportsCustomizedController::class, 'listStatements']
        )->name('reportscustomized.list-statements');

        Route::post(
            'reportscustomized/save-statement',
            [ReportsCustomizedController::class, 'saveStatement']
        )->name('reportscustomized.save-statement');

        Route::get(
            'reportscustomized/saved-statement/{id}',
            [ReportsCustomizedController::class, 'showSavedStatement']
        )->name('reportscustomized.saved-statement');

        Route::resource(
            'reportscustomized',
            ReportsCustomizedController::class
        );

        Route::resource(
            'reportscustomizedsettings',
            ReportsCustomizedSettingsController::class
        );
    });
