<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewCentralMemberController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewBusinessMemberController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewBusinessHistoryController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('central-members', [MembershipNewCentralMemberController::class, 'index'])
            ->name('central-members.index')
            ->middleware('permission:membership_new.central_members.view');

        Route::post('central-members', [MembershipNewCentralMemberController::class, 'store'])
            ->name('central-members.store')
            ->middleware('permission:membership_new.central_members.create');

        Route::get('central-members/{id}', [MembershipNewCentralMemberController::class, 'show'])
            ->name('central-members.show')
            ->middleware('permission:membership_new.central_members.view');

        Route::get('business-members', [MembershipNewBusinessMemberController::class, 'index'])
            ->name('business-members.index')
            ->middleware('permission:membership_new.business_members.view');

        Route::post('business-members/link', [MembershipNewBusinessMemberController::class, 'link'])
            ->name('business-members.link')
            ->middleware('permission:membership_new.business_members.link');

        Route::get('business-history', [MembershipNewBusinessHistoryController::class, 'index'])
            ->name('business-history.index')
            ->middleware('permission:membership_new.business_history.view');

        Route::post('business-history/ledger-entry', [MembershipNewBusinessHistoryController::class, 'ledgerEntry'])
            ->name('business-history.ledger-entry')
            ->middleware('permission:membership_new.business_history.create');
    });
