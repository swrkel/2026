<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Notifications\MyHealthNotificationController;
use Modules\MyHealthMembers\Http\Controllers\Notifications\MyHealthNotificationTemplateController;

Route::prefix('myhealth/notifications')->as('myhealth.notifications.')->group(function () {
    Route::get('/', [MyHealthNotificationController::class, 'index'])->name('index');
    Route::get('/create', [MyHealthNotificationController::class, 'create'])->name('create');
    Route::post('/', [MyHealthNotificationController::class, 'store'])->name('store');
    Route::post('/{notification}/sent', [MyHealthNotificationController::class, 'markSent'])->name('sent');
    Route::post('/{notification}/failed', [MyHealthNotificationController::class, 'markFailed'])->name('failed');

    Route::resource('templates', MyHealthNotificationTemplateController::class)->except(['show', 'destroy']);
});
