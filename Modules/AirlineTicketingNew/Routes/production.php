<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Admin\BackupController;
use Modules\AirlineTicketingNew\Http\Controllers\Admin\MonitoringController;

Route::get('admin/backups', [BackupController::class, 'index'])
    ->name('admin.backups.index')
    ->middleware('permission:airline_ticketing_new.backups.view');

Route::post('admin/backups', [BackupController::class, 'store'])
    ->name('admin.backups.store')
    ->middleware('permission:airline_ticketing_new.backups.create');

Route::get('admin/monitoring', [MonitoringController::class, 'index'])
    ->name('admin.monitoring.index')
    ->middleware('permission:airline_ticketing_new.monitoring.view');
