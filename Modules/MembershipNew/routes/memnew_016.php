<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewSettingsController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('settings', [MembershipNewSettingsController::class, 'index'])
            ->name('settings.index')
            ->middleware('permission:membership_new.settings.view');

        Route::post('settings/regions', [MembershipNewSettingsController::class, 'storeRegion'])
            ->name('settings.regions.store')
            ->middleware('permission:membership_new.settings.create');
        Route::patch('settings/regions/{regionId}', [MembershipNewSettingsController::class, 'updateRegion'])
            ->name('settings.regions.update')
            ->middleware('permission:membership_new.settings.create');
        Route::patch('settings/regions/{regionId}/status', [MembershipNewSettingsController::class, 'toggleRegionStatus'])
            ->name('settings.regions.toggle-status')
            ->middleware('permission:membership_new.settings.create');

        Route::post('settings/options', [MembershipNewSettingsController::class, 'storeOption'])
            ->name('settings.options.store')
            ->middleware('permission:membership_new.settings.create');
        Route::patch('settings/options/{optionId}', [MembershipNewSettingsController::class, 'updateOption'])
            ->name('settings.options.update')
            ->middleware('permission:membership_new.settings.create');
        Route::patch('settings/options/{optionId}/status', [MembershipNewSettingsController::class, 'toggleOptionStatus'])
            ->name('settings.options.toggle-status')
            ->middleware('permission:membership_new.settings.create');
    });
