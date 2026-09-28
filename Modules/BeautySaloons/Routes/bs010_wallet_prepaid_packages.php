<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\WalletController;
use Modules\BeautySaloons\Http\Controllers\PrepaidPackageController;

Route::group(['prefix' => 'beauty-saloons', 'as' => 'beauty-saloons.', 'middleware' => ['web', 'auth']], function () {
    Route::resource('wallets', WalletController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('wallets/{wallet}/top-up', [WalletController::class, 'topUp'])->name('wallets.top-up');
    Route::post('wallets/{wallet}/debit', [WalletController::class, 'debit'])->name('wallets.debit');
    Route::post('wallets/{wallet}/refund', [WalletController::class, 'refund'])->name('wallets.refund');

    Route::resource('prepaid-packages', PrepaidPackageController::class)->only(['index', 'create', 'store']);
    Route::post('prepaid-packages/sell', [PrepaidPackageController::class, 'sell'])->name('prepaid-packages.sell');
    Route::post('prepaid-packages/sales/{sale}/consume', [PrepaidPackageController::class, 'consume'])->name('prepaid-packages.consume');
    Route::get('reports/prepaid-package-utilization', [PrepaidPackageController::class, 'report'])->name('prepaid-packages.report');
});
