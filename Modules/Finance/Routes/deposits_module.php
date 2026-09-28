<?php

/*
 |------------------------------------------------------------------------------
 | Finance module - Deposits module routes (/deposits-module)
 |------------------------------------------------------------------------------
 |
 | Moved out of routes/web.php (and routes/tenant.php) so the Finance module owns
 | these URLs instead of the core router pointing at a core controller.
 |
 | The URLs are unchanged - still /deposits-module/... - so nothing bookmarked,
 | linked or scripted against them breaks. What changed is which class serves
 | them: Modules\Finance\Http\Controllers\Deposits\DepositsController rather
 | than App\Http\Controllers\DepositsController.
 |
 | Controllers are referenced by ::class rather than by the old string form,
 | because the string form is resolved against the ROOT controller namespace
 | (App\Http\Controllers) and would silently find the core class again.
 |
 | The IsSubscribed:deposits_module middleware is preserved exactly as it was.
 */

/*
 |------------------------------------------------------------------------------
 | Session middleware added.
 |------------------------------------------------------------------------------
 |
 | Reported: /deposits-module/account died with
 |     RuntimeException: Session store not set on request.
 |     at CheckSubscribed.php line 19 -> $request->session()
 |
 | This group applied ONLY 'IsSubscribed:deposits_module'. That alias is a route
 | middleware, not a group, so nothing here ever ran StartSession - and
 | $request->session() throws when no session store is bound. CheckSubscribed was
 | simply the first middleware to touch the session; SetSessionData, Timezone and
 | DayEnd all make the same assumption and would have failed next.
 |
 | The stack below is the SAME one the accounting_module routes in this module
 | already use, so both halves of Finance now behave identically:
 |
 |     web            - cookies, StartSession, CSRF
 |     auth           - these pages are not public
 |     SetSessionData - business/user context the controllers read
 |     language
 |     timezone
 |
 | IsSubscribed is kept and runs last, so the subscription check happens once the
 | session it depends on actually exists.
 */
    Route::group([
        'prefix' => 'deposits-module',
        'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'IsSubscribed:deposits_module'],
    ], function () {
        Route::get('/check-insufficient-balance-for-accounts', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getAccsForWhichToCheckInsufficientBalances']); // @eng 15/2

        Route::get('/get-account-dp', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getBankAccountDropDown']);

        Route::get('/get-account-group-name-dp', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getBankAccountByGroupDP']);

        // S673: {group_id?} is optional. A dependent dropdown reset to
        // "Please Select" posts an empty value, and a required parameter
        // then produced a 404 that surfaced to the user as "Failed".
        Route::get('/get-account-by-group-id/{group_id?}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getAccountByGroupId']);

        Route::get('/get-account-group-by-account/{type_id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getAccountGroupByAccount']);

        Route::get('/get-parent-account-by-type/{type_id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getParentAccountsByType']);

        Route::get('/account/image-modal', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'imageModal']);

        Route::resource('/account', \Modules\Finance\Http\Controllers\Deposits\DepositsController::class)->names('deposits.account');

        Route::get('/fund-transfer', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getFundTransfer']);

        Route::get('/account-number/{id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getAccNo']);

        Route::post('/fund-transfer', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'postFundTransfer']);

        Route::get('/cheque-list', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getChequeList']);

        Route::post('/cheque-deposit', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'postChequeDeposit']);

        Route::get('/cheque-deposit', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getChequeDeposit']);

        Route::get('/realize-cheque-deposit', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getRealizeChequeDeposit']);
        Route::get('/realize-cheque-list', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getRealizeChequeList']);
        Route::post('/realize-cheque-deposit', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'postRealizeChequeDeposit']);

        Route::get('/deposit/{id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getDeposit']);

        Route::post('/deposit', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'postDeposit']);

        Route::get('/get-account-balance/{id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'getAccountBalance']);

        Route::get('/list-deposit-transfer', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'listDepositTransfer']);

        Route::get('/cheques-ob-details', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'chequeObTransfer']);

        Route::get('/edit-deposit-transfer/{id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'editDepositTransfer']);

        Route::post('/update-deposit-transfer/{id}', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'updateDepositTransfer']);

    });
