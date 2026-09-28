<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthOperationTheatreDashboardController;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthOperationTheatreReportController;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthOperativeRecordController;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthPostOperativeNoteController;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthSurgeryChecklistController;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthSurgeryScheduleController;
use Modules\MyHealthMembers\Http\Controllers\OperationTheatre\MyHealthTheatreRoomController;

Route::prefix('my-health/operation-theatre')->name('myhealth.operation_theatre.')->group(function () {
    Route::get('/', [MyHealthOperationTheatreDashboardController::class, 'index'])->name('dashboard');
    Route::resource('schedules', MyHealthSurgeryScheduleController::class)->only(['index', 'create', 'store']);
    Route::post('schedules/{schedule}/status', [MyHealthSurgeryScheduleController::class, 'status'])->name('schedules.status');
    Route::resource('theatres', MyHealthTheatreRoomController::class)->only(['index', 'create', 'store']);
    Route::resource('checklists', MyHealthSurgeryChecklistController::class)->only(['index', 'create', 'store']);
    Route::resource('records', MyHealthOperativeRecordController::class)->only(['index', 'create', 'store']);
    Route::resource('post-op', MyHealthPostOperativeNoteController::class)->only(['index', 'create', 'store'])->names('post_op');
    Route::get('reports', [MyHealthOperationTheatreReportController::class, 'index'])->name('reports.index');
});
