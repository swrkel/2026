<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\AppointmentCalendarController;
use Modules\BeautySaloons\Http\Controllers\PosController;
use Modules\BeautySaloons\Http\Controllers\PackageController;
use Modules\BeautySaloons\Http\Controllers\CommissionController;
use Modules\BeautySaloons\Http\Controllers\OperationsReportController;

Route::group(['prefix' => 'beauty-saloons', 'middleware' => ['web', 'auth']], function () {
    Route::get('appointment-calendar', [AppointmentCalendarController::class, 'index'])->name('beauty-saloons.appointment-calendar.index');
    Route::get('appointment-calendar/events', [AppointmentCalendarController::class, 'events'])->name('beauty-saloons.appointment-calendar.events');

    Route::get('pos', [PosController::class, 'create'])->name('beauty-saloons.pos.create');
    Route::post('pos', [PosController::class, 'store'])->name('beauty-saloons.pos.store');
    Route::get('pos/{sale}/receipt', [PosController::class, 'receipt'])->name('beauty-saloons.pos.receipt');

    Route::resource('packages', PackageController::class)->names('beauty-saloons.packages');
    Route::resource('commissions', CommissionController::class)->names('beauty-saloons.commissions');

    Route::get('reports/daily-collection', [OperationsReportController::class, 'dailyCollection'])->name('beauty-saloons.reports.daily-collection');
    Route::get('reports/appointments', [OperationsReportController::class, 'appointments'])->name('beauty-saloons.reports.appointments');
    Route::get('reports/service-sales', [OperationsReportController::class, 'serviceSales'])->name('beauty-saloons.reports.service-sales');
    Route::get('reports/staff-commissions', [OperationsReportController::class, 'staffCommissions'])->name('beauty-saloons.reports.staff-commissions');
});
