<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\DailyActivityReport\Http\Controllers\DailyActivityReportController;

Route::middleware(['web', 'auth', 'language', 'tenant.context'])
    ->prefix('daily-activity-report')
    ->name('daily-activity-report.')
    ->group(function () {

        Route::get('/', [DailyActivityReportController::class, 'index'])->name('index');
    });
