<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessController;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessSessionController;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessReportController;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessSettingController;

Route::middleware(['web', 'auth'])->prefix('identity-access')->as('identityaccess.')->group(function () {
    Route::get('/', [IdentityAccessController::class, 'index'])->name('dashboard');
    Route::get('/sessions', [IdentityAccessSessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions/{id}/revoke', [IdentityAccessSessionController::class, 'revoke'])->name('sessions.revoke');
    Route::get('/reports', [IdentityAccessReportController::class, 'index'])->name('reports.index');
    Route::get('/settings', [IdentityAccessSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [IdentityAccessSettingController::class, 'store'])->name('settings.store');
});
