<?php

use Illuminate\Support\Facades\Route;
use Modules\PumperDashboardNew\Http\Controllers\AssetController;
use Modules\PumperDashboardNew\Http\Controllers\Auth\OperatorLoginController;
use Modules\PumperDashboardNew\Http\Middleware\EnsurePoneSchema;

Route::get('/assets/{type}/{file}', [AssetController::class, 'show'])
    ->where(['type' => 'css|js|images', 'file' => '[A-Za-z0-9._-]+'])
    ->name('assets.show');

Route::middleware(EnsurePoneSchema::class)->group(function (): void {
    Route::get('/login', [OperatorLoginController::class, 'show'])->name('login');
    Route::post('/login', [OperatorLoginController::class, 'login'])->name('login.store');
});
