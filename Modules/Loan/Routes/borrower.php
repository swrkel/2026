<?php

use Illuminate\Support\Facades\Route;
use Modules\Loan\Http\Middleware\EnsureLoanModuleEnabled;

/*
|--------------------------------------------------------------------------
| Borrower Portal Routes
|--------------------------------------------------------------------------
|
| Enterprise Self-Service Borrower Portal
|
| Customers can ONLY:
| - view own loans
| - view own schedules
| - view own repayments
| - view own notices
| - request settlements
| - request restructuring
|
| Multi-tenant safe.
| Ownership protected.
|
*/

Route::group([

    'middleware' => [

        'web',
        'auth',
        'SetSessionData',
        'language',
        'timezone',
        'tenant.context',
        'borrower',
        EnsureLoanModuleEnabled::class

    ],

    'prefix' => 'borrower',

    'namespace' =>
        'Modules\Loan\Http\Controllers\Borrower'

], function () {

    /*
    |--------------------------------------------------------------------------
    | Borrower Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        'dashboard',
        'BorrowerDashboardController@index'
    )->name(
        'borrower.dashboard'
    );

    /*
    |--------------------------------------------------------------------------
    | My Loans
    |--------------------------------------------------------------------------
    */

    Route::get(
        'loans',
        'BorrowerLoanController@index'
    )->name(
        'borrower.loans'
    );

    /*
    |--------------------------------------------------------------------------
    | Loan Details
    |--------------------------------------------------------------------------
    */

    Route::get(
        'loans/{id}',
        'BorrowerLoanController@show'
    )->name(
        'borrower.loans.show'
    );

    /*
    |--------------------------------------------------------------------------
    | Repayment Schedule
    |--------------------------------------------------------------------------
    */

    Route::get(
        'loans/{id}/schedule',
        'BorrowerLoanScheduleController@index'
    )->name(
        'borrower.loan.schedule'
    );

    /*
    |--------------------------------------------------------------------------
    | Repayment History
    |--------------------------------------------------------------------------
    */

    Route::get(
        'loans/{id}/repayments',
        'BorrowerRepaymentController@index'
    )->name(
        'borrower.loan.repayments'
    );

    /*
    |--------------------------------------------------------------------------
    | Penalties
    |--------------------------------------------------------------------------
    */

    Route::get(
        'loans/{id}/penalties',
        'BorrowerPenaltyController@index'
    )->name(
        'borrower.loan.penalties'
    );

    /*
    |--------------------------------------------------------------------------
    | Notices & Alerts
    |--------------------------------------------------------------------------
    */

    Route::get(
        'notifications',
        'BorrowerNotificationController@index'
    )->name(
        'borrower.notifications'
    );

    /*
    |--------------------------------------------------------------------------
    | Settlement Requests
    |--------------------------------------------------------------------------
    */

    Route::get(
        'settlements',
        'BorrowerSettlementController@index'
    )->name(
        'borrower.settlements'
    );

    Route::post(
        'settlements/request',
        'BorrowerSettlementController@store'
    )->name(
        'borrower.settlements.store'
    );

    /*
    |--------------------------------------------------------------------------
    | Restructuring Requests
    |--------------------------------------------------------------------------
    */

    Route::get(
        'restructuring',
        'BorrowerRestructuringController@index'
    )->name(
        'borrower.restructuring'
    );

    Route::post(
        'restructuring/request',
        'BorrowerRestructuringController@store'
    )->name(
        'borrower.restructuring.store'
    );

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get(
        'profile',
        'BorrowerProfileController@index'
    )->name(
        'borrower.profile'
    );

    Route::post(
        'profile/update',
        'BorrowerProfileController@update'
    )->name(
        'borrower.profile.update'
    );
});