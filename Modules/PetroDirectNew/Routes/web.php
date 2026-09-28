<?php

use Illuminate\Support\Facades\Route;
use Modules\PetroDirectNew\Http\Controllers\DashboardController;
use Modules\PetroDirectNew\Http\Controllers\PumperManagementController;
use Modules\PetroDirectNew\Http\Controllers\ReportController;
use Modules\PetroDirectNew\Http\Controllers\SettlementController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::get('/settlements', [SettlementController::class, 'index'])->name('settlements.index');
Route::get('/settlements/create', [SettlementController::class, 'create'])->name('settlements.create');
Route::post('/settlements', [SettlementController::class, 'store'])->name('settlements.store');
Route::get('/settlements/{id}', [SettlementController::class, 'show'])->whereNumber('id')->name('settlements.show');
Route::get('/settlements/{id}/edit', [SettlementController::class, 'edit'])->whereNumber('id')->name('settlements.edit');
Route::put('/settlements/{id}', [SettlementController::class, 'update'])->whereNumber('id')->name('settlements.update');
Route::delete('/settlements/{id}', [SettlementController::class, 'destroy'])->whereNumber('id')->name('settlements.destroy');
Route::post('/settlements/{id}/finalize', [SettlementController::class, 'finalize'])->whereNumber('id')->name('settlements.finalize');
Route::get('/settlements/{id}/print', [SettlementController::class, 'print'])->whereNumber('id')->name('settlements.print');

Route::get('/pumper-management', [PumperManagementController::class, 'index'])->name('pumper-management.index');
Route::get('/pumper-management/tab/{tab}', [PumperManagementController::class, 'tab'])->where('tab', '[A-Za-z0-9_]+')->name('pumper-management.tab');
Route::post('/pumper-management/operators/synchronize', [PumperManagementController::class, 'syncOperators'])->name('pumper-management.operators.synchronize');
Route::get('/pumper-management/operator-users', [PumperManagementController::class, 'searchOperatorUsers'])->name('pumper-management.operator-users.search');
Route::post('/pumper-management/operators', [PumperManagementController::class, 'storeOperator'])->name('pumper-management.operators.store');
Route::get('/pumper-management/operators/{id}', [PumperManagementController::class, 'showOperator'])->whereNumber('id')->name('pumper-management.operators.show');
Route::put('/pumper-management/operators/{id}', [PumperManagementController::class, 'updateOperator'])->whereNumber('id')->name('pumper-management.operators.update');
Route::delete('/pumper-management/operators/{id}', [PumperManagementController::class, 'destroyOperator'])->whereNumber('id')->name('pumper-management.operators.destroy');
Route::post('/pumper-management/operators/{id}/toggle', [PumperManagementController::class, 'toggleOperator'])->whereNumber('id')->name('pumper-management.operators.toggle');
Route::post('/pumper-management/adjustments', [PumperManagementController::class, 'storeAdjustment'])->name('pumper-management.adjustments.store');
Route::post('/pumper-management/day-entries', [PumperManagementController::class, 'storePumperDayEntry'])->name('pumper-management.day-entries.store');
Route::post('/pumper-management/unload-stock', [PumperManagementController::class, 'storeUnloadStock'])->name('pumper-management.unload-stock.store');
Route::post('/pumper-management/tanks', [PumperManagementController::class, 'storeTank'])->name('pumper-management.tanks.store');
Route::post('/pumper-management/pumps', [PumperManagementController::class, 'storePump'])->name('pumper-management.pumps.store');
Route::post('/pumper-management/assignments', [PumperManagementController::class, 'assign'])->name('pumper-management.assignments.store');
Route::post('/pumper-management/meters', [PumperManagementController::class, 'recordMeter'])->name('pumper-management.meters.store');
Route::post('/pumper-management/assignments/{id}/close', [PumperManagementController::class, 'closeAssignment'])->whereNumber('id')->name('pumper-management.assignments.close');
Route::post('/pumper-management/dips', [PumperManagementController::class, 'storeDip'])->name('pumper-management.dips.store');
Route::post('/pumper-management/transfers', [PumperManagementController::class, 'storeTransfer'])->name('pumper-management.transfers.store');
Route::post('/pumper-management/collections', [PumperManagementController::class, 'generateCollection'])->name('pumper-management.collections.store');
Route::post('/pumper-management/shifts/{id}/close', [PumperManagementController::class, 'closeShift'])->whereNumber('id')->name('pumper-management.shifts.close');

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');
