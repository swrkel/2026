<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Audit\MyHealthStandaloneAuditController;

Route::prefix('my-health/audit')->name('myhealth.audit.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [MyHealthStandaloneAuditController::class, 'index'])->name('index');
});
