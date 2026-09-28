<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingUI\Http\Controllers\BankingReleaseAuditController;

Route::middleware(['web', 'auth'])->prefix('banking/ui-audit')->name('banking.ui.audit.')->group(function () {
    Route::get('/', [BankingReleaseAuditController::class, 'index'])->name('index');
    Route::get('/sidebar', [BankingReleaseAuditController::class, 'sidebar'])->name('sidebar');
    Route::get('/routes', [BankingReleaseAuditController::class, 'routes'])->name('routes');
    Route::get('/permissions', [BankingReleaseAuditController::class, 'permissions'])->name('permissions');
    Route::get('/checklist', [BankingReleaseAuditController::class, 'checklist'])->name('checklist');
});
