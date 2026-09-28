<?php

use Illuminate\Support\Facades\Route;
use Modules\Chequer\Http\Controllers\DashboardController;
use Modules\Chequer\Http\Controllers\BankAccountController;
use Modules\Chequer\Http\Controllers\TemplateController;
use Modules\Chequer\Http\Controllers\ChequeBookController;
use Modules\Chequer\Http\Controllers\ChequeLeafController;
use Modules\Chequer\Http\Controllers\WriteChequeController;
use Modules\Chequer\Http\Controllers\SettingController;
use Modules\Chequer\Http\Controllers\PrintCalibrationController;
use Modules\Chequer\Http\Controllers\PrintHistoryController;

try {
    app('view')->addNamespace('chequer', dirname(__DIR__) . '/Resources/views');
} catch (\Throwable $e) {
    \Log::warning('Chequer route view namespace fallback failed', ['message' => $e->getMessage()]);
}

$chequerMiddleware = ['web', 'auth', 'SetSessionData', 'language', 'timezone'];

Route::middleware($chequerMiddleware)
    ->prefix('chequer-module')
    ->as('chequer.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');

        Route::get('templates/{id}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
        Route::get('templates/{id}/test-print', [TemplateController::class, 'testPrint'])->name('templates.test-print');
        Route::resource('templates', TemplateController::class)->except(['show']);

        Route::resource('cheque-books', ChequeBookController::class)->except(['show']);
        Route::get('cheque-leaves', [ChequeLeafController::class, 'index'])->name('cheque-leaves.index');

        Route::get('write-cheque', [WriteChequeController::class, 'index'])->name('write-cheque.index');
        Route::get('write-cheque/create', [WriteChequeController::class, 'create'])->name('write-cheque.create');
        Route::post('write-cheque', [WriteChequeController::class, 'store'])->name('write-cheque.store');
        Route::get('write-cheque/{id}/print', [WriteChequeController::class, 'print'])->name('write-cheque.print');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'store'])->name('settings.store');

        Route::get('print-calibration', [PrintCalibrationController::class, 'index'])->name('print-calibration.index');
        Route::post('print-calibration', [PrintCalibrationController::class, 'store'])->name('print-calibration.store');
        Route::get('print-history', [PrintHistoryController::class, 'index'])->name('print-history.index');

        // Backward-compatible in-module URLs used during migration.
        Route::get('default-settings', [SettingController::class, 'index'])->name('default-settings.index');
        Route::post('default-settings', [SettingController::class, 'store'])->name('default-settings.store');
        Route::get('cheque-numbers', [ChequeLeafController::class, 'index'])->name('cheque-numbers.index');
        Route::get('cheque-number-entries', [ChequeLeafController::class, 'index'])->name('cheque-numbers-m-entries.index');
        Route::get('payees', [WriteChequeController::class, 'index'])->name('payees.index');
        Route::get('stamps', [TemplateController::class, 'index'])->name('stamps.index');
        Route::get('cancelled-cheques', [WriteChequeController::class, 'index'])->name('cancelled-cheques.index');
        Route::get('deleted-cheques', [WriteChequeController::class, 'index'])->name('deleted-cheques.index');
    });

// S383: Only /chequer redirects are kept here because /public/chequer may exist on some servers
// and can bypass Laravel. Legacy Cheque Writing URLs such as /cheque-templates, /cheque-write,
// /default_setting and /cheque-numbers must remain with the old production module until the
// new Chequer module is fully tested.
Route::middleware($chequerMiddleware)->group(function () {
    Route::get('chequer', fn() => redirect('/chequer-module'));
    Route::get('chequer/{any}', fn($any = null) => redirect('/chequer-module/' . ltrim((string) $any, '/')))->where('any', '.*');
});
