<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Audit\DisnewProductionAuditController;

Route::group(['middleware'=>['web','auth','tenant']], function(){
    Route::prefix('distribution-new/audit')->name('distributionnew.audit.')->group(function(){
        Route::get('/', [DisnewProductionAuditController::class,'index'])->name('index');
        Route::get('/permissions', [DisnewProductionAuditController::class,'permissions'])->name('permissions');
        Route::get('/menu', [DisnewProductionAuditController::class,'menu'])->name('menu');
        Route::get('/routes', [DisnewProductionAuditController::class,'routes'])->name('routes');
        Route::get('/sql', [DisnewProductionAuditController::class,'sql'])->name('sql');
        Route::get('/ui', [DisnewProductionAuditController::class,'ui'])->name('ui');
        Route::post('/run', [DisnewProductionAuditController::class,'run'])->name('run');
    });
});
