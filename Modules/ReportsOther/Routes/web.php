<?php

use Illuminate\Support\Facades\Route;
use Modules\ReportsOther\Http\Controllers\AssetController;
use Modules\ReportsOther\Http\Controllers\CashReceiptController;
use Modules\ReportsOther\Http\Controllers\NumberingController;
use Modules\ReportsOther\Http\Controllers\ReceiptEditController;
use Modules\ReportsOther\Http\Controllers\ReceiptEntryController;
use Modules\ReportsOther\Http\Controllers\ReceiptPreviewController;
use Modules\ReportsOther\Http\Controllers\ReceiptShareController;
use Modules\ReportsOther\Http\Controllers\ReceiptViewController;
use Modules\ReportsOther\Http\Controllers\SharedDownloadController;
use Modules\ReportsOther\Http\Controllers\SourceMappingController;
use Modules\ReportsOther\Http\Middleware\ReportsOtherAccess;

Route::middleware(['web'])->group(function () {
    Route::get('/reports-other/shared/{token}', SharedDownloadController::class)
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('reports-other.shared.download');

    Route::get('/reports-other/assets/{type}/{file}', AssetController::class)
        ->where('type', 'css|js')
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('reports-other.asset');

    Route::prefix('reports-other')
        ->name('reports-other.')
        ->middleware([ReportsOtherAccess::class, 'check.route.permission'])
        ->group(function () {
            Route::redirect('/', '/reports-other/cash-receipt');
            Route::get('/cash-receipt', [CashReceiptController::class, 'index'])
                ->name('cash-receipt.index');

            Route::get('/cash-receipt/preview', ReceiptPreviewController::class)
                ->name('cash-receipt.preview');
            Route::post('/cash-receipt/receipts', [ReceiptEntryController::class, 'store'])
                ->name('cash-receipt.receipts.store');
            Route::get('/cash-receipt/receipts/{receipt}', [ReceiptViewController::class, 'show'])
                ->name('cash-receipt.receipts.show');
            Route::get('/cash-receipt/receipts/{receipt}/print', [ReceiptViewController::class, 'print'])
                ->name('cash-receipt.receipts.print');
            Route::get('/cash-receipt/receipts/{receipt}/edit', [ReceiptEditController::class, 'edit'])
                ->name('cash-receipt.receipts.edit');
            Route::put('/cash-receipt/receipts/{receipt}', [ReceiptEditController::class, 'update'])
                ->name('cash-receipt.receipts.update');
            Route::post('/cash-receipt/receipts/{receipt}/share', [ReceiptShareController::class, 'receipt'])
                ->name('cash-receipt.receipts.share');
            Route::post('/cash-receipt/list/share', [ReceiptShareController::class, 'list'])
                ->name('cash-receipt.list.share');

            Route::post('/cash-receipt/source-mappings', [SourceMappingController::class, 'store'])
                ->name('cash-receipt.source-mappings.store');
            Route::delete('/cash-receipt/source-mappings/{source}', [SourceMappingController::class, 'destroy'])
                ->name('cash-receipt.source-mappings.destroy');

            Route::post('/cash-receipt/numbering', [NumberingController::class, 'store'])
                ->name('cash-receipt.numbering.store');
        });
});
