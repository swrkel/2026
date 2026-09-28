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

use Illuminate\Support\Facades\Route;
use Modules\MyAuto\Http\Controllers\MyAutoController;
use Modules\MyAuto\Http\Controllers\MyAutoSubscriptionController;

Route::group([
    'middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'],
    'prefix' => 'myauto'
], function () {

    // Home (index)
    Route::get('/', [MyAutoController::class, 'index'])
        ->name('myauto.index');

    // Settings
    Route::get('/settings', [MyAutoController::class, 'settings'])
        ->name('myauto.settings');

    Route::post('/settings', [MyAutoController::class, 'storeSettings'])
        ->name('myauto.settings.store');

    // Other actions
    Route::post('/update-meter', [MyAutoController::class, 'updateMeter']);
    Route::post('/add-trip-income', [MyAutoController::class, 'addTripIncome']);
    Route::post('/add-expense', [MyAutoController::class, 'addExpense'])->name('myauto.addExpense');
    Route::post('/update-field', [MyAutoController::class, 'updateField'])
        ->name('myauto.updateField');

    Route::post('/verify-passcode', [MyAutoController::class, 'verifyPasscode'])
        ->name('myauto.verifyPasscode');

    Route::post('/change-passcode', [MyAutoController::class, 'changePasscode'])
        ->name('myauto.changePasscode');

    Route::post('/myauto/update-multiple', [MyAutoController::class, 'updateMultiple'])
        ->name('myauto.updateMultiple');

    Route::get('/myauto/today-income-details/{log}', [MyAutoController::class, 'todayIncomeDetails'])
        ->name('myauto.todayIncomeDetails');

    Route::get('/today-expense-details/{id}', [MyAutoController::class, 'todayExpenseDetails'])
        ->name('myauto.todayExpenseDetails');

    Route::get('/past-details/{id}', [MyAutoController::class, 'pastDetails'])
        ->name('myauto.pastDetails');
});


Route::middleware(['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'])
    ->prefix('myauto')->group(function () {

        Route::get(
            '/subscription/renew',
            [MyAutoSubscriptionController::class, 'renew']
        )->name('myauto.subscription.renew');

        Route::post(
            '/subscription/pay-online',
            [MyAutoSubscriptionController::class, 'payOnline']
        )->name('myauto.subscription.pay.online');

        Route::post(
            '/subscription/pay-offline',
            [MyAutoSubscriptionController::class, 'payOffline']
        )->name('myauto.subscription.pay.offline');

        Route::post(
            '/subscription/payment-callback',
            [MyAutoSubscriptionController::class, 'paymentCallback']
        )->name('myauto.subscription.callback');

        Route::post(
            '/subscription/start-checkout',
            [MyAutoSubscriptionController::class, 'startCheckout']
        )->name('myauto.subscription.startCheckout');
    });
