<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrEssController;

Route::prefix('hr-manager/ess')->middleware(['web','auth'])->name('hrmanager.ess.')->group(function(){
    Route::get('/', [HrEssController::class, 'index'])->name('index');
    Route::post('/requests', [HrEssController::class, 'storeRequest'])->name('requests.store');
    Route::post('/tickets', [HrEssController::class, 'storeTicket'])->name('tickets.store');
});

Route::prefix('hr/ess')->middleware(['web','auth'])->name('hr.ess.')->group(function(){
    Route::get('/', [HrEssController::class, 'index'])->name('dashboard');
    Route::post('/requests', [HrEssController::class, 'storeRequest'])->name('requests.store');
    Route::post('/tickets', [HrEssController::class, 'storeTicket'])->name('tickets.store');
});
