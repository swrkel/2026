<?php
use Illuminate\Support\Facades\Route;
Route::prefix('api/leads-new')->name('api.leads-new.')->middleware(['api'])->group(function(){
    Route::get('leads', [\Modules\LeadsNew\Http\Controllers\Api\LeadsNewLeadApiController::class, 'index'])->name('leads.index');
    Route::post('leads', [\Modules\LeadsNew\Http\Controllers\Api\LeadsNewLeadApiController::class, 'store'])->name('leads.store');
});


Route::prefix('leads-new/v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('summary', [\Modules\LeadsNew\Http\Controllers\Api\LeadsNewStage8ApiController::class, 'summary']);
    Route::get('search', [\Modules\LeadsNew\Http\Controllers\Api\LeadsNewStage8ApiController::class, 'search']);
});
