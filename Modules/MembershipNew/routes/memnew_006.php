<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewDuplicateController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewBusinessBalanceController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewOutletTransactionController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('central-members/duplicates', [MembershipNewDuplicateController::class, 'index'])
            ->name('central-members.duplicates')
            ->middleware('permission:membership_new.central_members.view');

        Route::post('central-members/duplicates/scan', [MembershipNewDuplicateController::class, 'scan'])
            ->name('central-members.duplicates.scan')
            ->middleware('permission:membership_new.central_members.edit');

        Route::post('central-members/duplicates/{candidateId}/resolve', [MembershipNewDuplicateController::class, 'resolve'])
            ->name('central-members.duplicates.resolve')
            ->middleware('permission:membership_new.central_members.edit');

        Route::get('business-balances', [MembershipNewBusinessBalanceController::class, 'index'])
            ->name('business-balances.index')
            ->middleware('permission:membership_new.business_history.view');

        Route::get('outlet-transactions', [MembershipNewOutletTransactionController::class, 'index'])
            ->name('outlet-transactions.index')
            ->middleware('permission:membership_new.points.view');

        Route::post('outlet-transactions', [MembershipNewOutletTransactionController::class, 'store'])
            ->name('outlet-transactions.store')
            ->middleware('permission:membership_new.points.earn');

        Route::post('outlet-transactions/{queueId}/process', [MembershipNewOutletTransactionController::class, 'process'])
            ->name('outlet-transactions.process')
            ->middleware('permission:membership_new.points.earn');
    });
