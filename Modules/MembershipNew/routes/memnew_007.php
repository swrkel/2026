<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewMergeController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewBusinessStatementController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewDividendPayoutController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('member-merge', [MembershipNewMergeController::class, 'index'])
            ->name('member-merge.index')
            ->middleware('permission:membership_new.central_members.edit');

        Route::post('member-merge', [MembershipNewMergeController::class, 'store'])
            ->name('member-merge.store')
            ->middleware('permission:membership_new.central_members.edit');

        Route::post('member-merge/{requestId}/approve', [MembershipNewMergeController::class, 'approve'])
            ->name('member-merge.approve')
            ->middleware('permission:membership_new.central_members.edit');

        Route::post('member-merge/{requestId}/process', [MembershipNewMergeController::class, 'process'])
            ->name('member-merge.process')
            ->middleware('permission:membership_new.central_members.edit');

        Route::get('business-statement', [MembershipNewBusinessStatementController::class, 'index'])
            ->name('business-statement.index')
            ->middleware('permission:membership_new.business_history.view');

        Route::get('dividend-payouts', [MembershipNewDividendPayoutController::class, 'index'])
            ->name('dividend-payouts.index')
            ->middleware('permission:membership_new.dividends.view');

        Route::post('dividend-payouts/{paymentId}/pay', [MembershipNewDividendPayoutController::class, 'pay'])
            ->name('dividend-payouts.pay')
            ->middleware('permission:membership_new.dividends.edit');

        Route::post('dividend-payouts/{payoutId}/reverse', [MembershipNewDividendPayoutController::class, 'reverse'])
            ->name('dividend-payouts.reverse')
            ->middleware('permission:membership_new.dividends.edit');
    });
