<?php

use Illuminate\Support\Facades\Route;
use Modules\Ran\Http\Controllers\AssetController;
use Modules\Ran\Http\Controllers\DashboardController;
use Modules\Ran\Http\Controllers\SetupController;
use Modules\Ran\Http\Controllers\Masters\ArtisanController;
use Modules\Ran\Http\Controllers\Masters\DesignController;
use Modules\Ran\Http\Controllers\Masters\GemstoneController;
use Modules\Ran\Http\Controllers\Masters\MetalController;
use Modules\Ran\Http\Controllers\Masters\PurityController;

Route::prefix('ran')->as('ran.')->middleware('web')->group(function (): void {
    Route::get('assets/{type}/{file}', [AssetController::class, 'show'])->name('assets');
});

Route::prefix('ran')->as('ran.')->middleware(['web', 'auth', 'ran.enabled'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('ran.page:ran.dashboard.view')->name('dashboard');
    Route::post('setup/run', [SetupController::class, 'run'])
        ->middleware('ran.page:ran.settings.manage')->name('setup.run');

    Route::prefix('masters')->as('masters.')->group(function (): void {
        foreach ([
            'metals' => MetalController::class,
            'purities' => PurityController::class,
            'gemstones' => GemstoneController::class,
            'artisans' => ArtisanController::class,
            'designs' => DesignController::class,
        ] as $uri => $controller) {
            Route::resource($uri, $controller)->only(['index'])
                ->middleware('ran.page:ran.masters.view');
            Route::resource($uri, $controller)->only(['create', 'store', 'edit', 'update', 'destroy'])
                ->middleware('ran.page:ran.masters.manage');
        }
    });
});
