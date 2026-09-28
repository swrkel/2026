<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Settings\YearClosureController;

/*
| Close Financial Year. Under the finance prefix with the rest of the module,
| and named finance.year-closure.* so it cannot collide with anything in core.
*/
Route::group([
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'],
    'prefix' => 'finance',
], function () {
    Route::get('/year-closure', [YearClosureController::class, 'index'])->name('finance.year-closure.index');
    Route::post('/year-closure/close', [YearClosureController::class, 'close'])->name('finance.year-closure.close');
    Route::post('/year-closure/{id}/reopen', [YearClosureController::class, 'reopen'])->name('finance.year-closure.reopen');
});
