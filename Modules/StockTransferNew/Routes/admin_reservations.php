<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\TransferReservationControlController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/admin/reservations')
    ->as('stock-transfer-new.admin.reservations.')
    ->group(function () {
        Route::get('/', [TransferReservationControlController::class, 'index'])->name('index');
        Route::get('/candidates', [TransferReservationControlController::class, 'candidates'])->name('candidates');
        Route::post('/candidate/{transferLineId}/reserve', [TransferReservationControlController::class, 'reserve'])->name('reserve');
        Route::post('/{reservationId}/release', [TransferReservationControlController::class, 'release'])->name('release');
        Route::post('/expire-overdue', [TransferReservationControlController::class, 'expire'])->name('expire');
        Route::get('/export/csv', [TransferReservationControlController::class, 'export'])->name('export');
    });
