<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrEmployeeDocumentController;

Route::prefix('hr-manager/documents')->middleware(['web','auth'])->name('hrmanager.documents.')->group(function(){
    Route::get('/', [HrEmployeeDocumentController::class, 'index'])->name('index');
    Route::post('/files', [HrEmployeeDocumentController::class, 'storeDocument'])->name('files.store');
    Route::post('/requests', [HrEmployeeDocumentController::class, 'storeRequest'])->name('requests.store');
});

Route::prefix('hr/documents')->middleware(['web','auth'])->name('hr.documents.')->group(function(){
    Route::get('/', [HrEmployeeDocumentController::class, 'index'])->name('dashboard');
    Route::post('/files', [HrEmployeeDocumentController::class, 'storeDocument'])->name('files.store');
    Route::post('/requests', [HrEmployeeDocumentController::class, 'storeRequest'])->name('requests.store');
});
