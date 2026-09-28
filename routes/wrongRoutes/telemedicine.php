<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Telemedicine\MyHealthDoctorScheduleController;
use Modules\MyHealthMembers\Http\Controllers\Telemedicine\MyHealthTelemedicineAppointmentController;
use Modules\MyHealthMembers\Http\Controllers\Telemedicine\MyHealthTelemedicineDashboardController;

Route::prefix('myhealth/telemedicine')->as('myhealth.telemedicine.')->group(function () {
    Route::get('/', [MyHealthTelemedicineDashboardController::class, 'index'])->name('dashboard');

    Route::get('/schedules', [MyHealthDoctorScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/create', [MyHealthDoctorScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/schedules', [MyHealthDoctorScheduleController::class, 'store'])->name('schedules.store');

    Route::get('/appointments', [MyHealthTelemedicineAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [MyHealthTelemedicineAppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [MyHealthTelemedicineAppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}', [MyHealthTelemedicineAppointmentController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{appointment}/open-session', [MyHealthTelemedicineAppointmentController::class, 'openSession'])->name('appointments.open-session');
    Route::get('/appointments/{appointment}/waiting-room', [MyHealthTelemedicineAppointmentController::class, 'waitingRoom'])->name('waiting-room');
});
