<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Pharmacy\MyHealthDispenseController;
use Modules\MyHealthMembers\Http\Controllers\Pharmacy\MyHealthMedicineController;
use Modules\MyHealthMembers\Http\Controllers\Pharmacy\MyHealthPharmacyController;
use Modules\MyHealthMembers\Http\Controllers\Pharmacy\MyHealthStockController;

Route::prefix('myhealth/pharmacy')->as('myhealth.pharmacy.')->group(function () {
    Route::get('/', [MyHealthPharmacyController::class, 'dashboard'])->name('dashboard');

    Route::get('/medicines', [MyHealthMedicineController::class, 'index'])->name('medicines.index');
    Route::get('/medicines/create', [MyHealthMedicineController::class, 'create'])->name('medicines.create');
    Route::post('/medicines', [MyHealthMedicineController::class, 'store'])->name('medicines.store');

    Route::get('/stock', [MyHealthStockController::class, 'index'])->name('stock.index');
    Route::get('/stock/create', [MyHealthStockController::class, 'create'])->name('stock.create');
    Route::post('/stock', [MyHealthStockController::class, 'store'])->name('stock.store');

    Route::get('/dispensing', [MyHealthDispenseController::class, 'index'])->name('dispensing.index');
    Route::get('/dispensing/create', [MyHealthDispenseController::class, 'create'])->name('dispensing.create');
    Route::post('/dispensing', [MyHealthDispenseController::class, 'store'])->name('dispensing.store');
    Route::get('/dispensing/{dispense}', [MyHealthDispenseController::class, 'show'])->name('dispensing.show');
});
