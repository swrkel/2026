<?php
use Illuminate\Support\Facades\Route;
use Modules\Tailoring\OrderManagement\Http\Controllers\TailoringQuotationController;
use Modules\Tailoring\OrderManagement\Http\Controllers\TailoringOrderController;
use Modules\Tailoring\OrderManagement\Http\Controllers\TailoringJobCardController;

Route::middleware(['web', 'auth'])->prefix('tailoring')->group(function () {
    Route::get('quotations', [TailoringQuotationController::class, 'index'])->name('tailoring.quotations.index');
    Route::get('quotations/create', [TailoringQuotationController::class, 'create'])->name('tailoring.quotations.create');
    Route::get('quotations/{id}', [TailoringQuotationController::class, 'show'])->name('tailoring.quotations.show');
    Route::get('quotations/{id}/convert', [TailoringQuotationController::class, 'convert'])->name('tailoring.quotations.convert');

    Route::get('orders', [TailoringOrderController::class, 'index'])->name('tailoring.orders.index');
    Route::get('orders/create', [TailoringOrderController::class, 'create'])->name('tailoring.orders.create');
    Route::get('orders/{id}', [TailoringOrderController::class, 'show'])->name('tailoring.orders.show');

    Route::get('job-cards', [TailoringJobCardController::class, 'index'])->name('tailoring.job_cards.index');
    Route::get('job-cards/{id}', [TailoringJobCardController::class, 'show'])->name('tailoring.job_cards.show');
    Route::get('job-cards/{id}/print', [TailoringJobCardController::class, 'print'])->name('tailoring.job_cards.print');
});
