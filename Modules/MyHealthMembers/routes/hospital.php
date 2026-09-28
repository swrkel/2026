<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Hospital\MyHealthAppointmentController;
use Modules\MyHealthMembers\Http\Controllers\Hospital\MyHealthHospitalDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Hospital\MyHealthQueueController;
use Modules\MyHealthMembers\Http\Controllers\Hospital\MyHealthReceptionController;
use Modules\MyHealthMembers\Http\Controllers\Hospital\MyHealthScheduleController;

Route::prefix('myhealth/hospital')->as('myhealth.hospital.')->group(function () {
    Route::get('/', [MyHealthHospitalDashboardController::class, 'index'])->name('dashboard');
    Route::get('/reception', [MyHealthReceptionController::class, 'index'])->name('reception.index');
    Route::get('/queue', [MyHealthQueueController::class, 'index'])->name('queue.index');

    Route::get('/appointments', [MyHealthAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [MyHealthAppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [MyHealthAppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/appointments/{appointment}/status', [MyHealthAppointmentController::class, 'status'])->name('appointments.status');

    Route::get('/doctor-schedules', [MyHealthScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/doctor-schedules/create', [MyHealthScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/doctor-schedules', [MyHealthScheduleController::class, 'store'])->name('schedules.store');
});
