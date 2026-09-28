<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->prefix('finance')->name('finance.')->group(function () {
    Route::any('cheques/postdated', [\Modules\Finance\Http\Controllers\Cheques\PostdatedChequeController::class, 'index'])->name('cheques.postdated.index');
    Route::any('cheques/realized', [\Modules\Finance\Http\Controllers\Cheques\RealizedChequeController::class, 'index'])->name('cheques.realized.index');
});
