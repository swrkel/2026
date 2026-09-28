<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Documents\MyHealthDocumentController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/documents/{member}', [MyHealthDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents/{member}', [MyHealthDocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/download/{document}', [MyHealthDocumentController::class, 'download'])->name('documents.download');
});
