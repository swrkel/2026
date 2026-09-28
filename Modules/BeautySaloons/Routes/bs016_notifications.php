<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\NotificationController;
use Modules\BeautySaloons\Http\Controllers\NotificationReportController;

Route::prefix('beauty-saloons/notifications')->as('beautysaloons.notifications.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/templates', [NotificationController::class, 'templates'])->name('templates');
    Route::get('/templates/create', [NotificationController::class, 'createTemplate'])->name('templates.create');
    Route::post('/templates', [NotificationController::class, 'storeTemplate'])->name('templates.store');
    Route::get('/settings', [NotificationController::class, 'settings'])->name('settings');
    Route::post('/settings', [NotificationController::class, 'saveSettings'])->name('settings.save');
    Route::post('/test', [NotificationController::class, 'test'])->name('test');
    Route::get('/reports/delivery', [NotificationReportController::class, 'delivery'])->name('reports.delivery');
});
