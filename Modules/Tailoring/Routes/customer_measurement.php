<?php
use Illuminate\Support\Facades\Route;
use Modules\Tailoring\CustomerCentre\Http\Controllers\TailoringCustomerCentreController;
use Modules\Tailoring\MeasurementCentre\Http\Controllers\TailoringMeasurementCentreController;

Route::middleware(['web', 'auth'])->prefix('tailoring')->group(function () {
    Route::get('customer-centre', [TailoringCustomerCentreController::class, 'index'])->name('tailoring.customer_centre.index');
    Route::get('customer-centre/create', [TailoringCustomerCentreController::class, 'create'])->name('tailoring.customer_centre.create');
    Route::get('customer-centre/{id}', [TailoringCustomerCentreController::class, 'show'])->name('tailoring.customer_centre.show');
    Route::get('measurement-centre', [TailoringMeasurementCentreController::class, 'index'])->name('tailoring.measurement_centre.index');
    Route::get('measurement-centre/create', [TailoringMeasurementCentreController::class, 'create'])->name('tailoring.measurement_centre.create');
    Route::get('measurement-centre/customer/{customerId}/compare', [TailoringMeasurementCentreController::class, 'compare'])->name('tailoring.measurement_centre.compare');
});
