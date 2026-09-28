<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewCardController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewCardScanController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewCustomerSyncController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewDashboardController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewDividendController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewExportController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewLinkedBusinessController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewMemberController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewPaymentController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewPlanController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewPointRuleController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewPointTransactionController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewRedemptionController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewReportController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewShareController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        // Main entry points. Keep both URLs valid so existing sidebar/bookmarks do not break.
        Route::get('/', [MembershipNewDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:membership_new.dashboard.view');
        Route::get('dashboard', [MembershipNewDashboardController::class, 'index'])
            ->name('dashboard.index')
            ->middleware('permission:membership_new.dashboard.view');

        // Members.
        Route::get('members', [MembershipNewMemberController::class, 'index'])
            ->name('members.index')->middleware('permission:membership_new.members.view');
        Route::get('members/create', [MembershipNewMemberController::class, 'create'])
            ->name('members.create')->middleware('permission:membership_new.members.create');
        Route::post('members', [MembershipNewMemberController::class, 'store'])
            ->name('members.store')->middleware('permission:membership_new.members.create');
        Route::get('members/{member}', [MembershipNewMemberController::class, 'show'])
            ->name('members.show')->middleware('permission:membership_new.members.view');
        Route::get('members/{member}/edit', [MembershipNewMemberController::class, 'edit'])
            ->name('members.edit')->middleware('permission:membership_new.members.edit');
        Route::put('members/{member}', [MembershipNewMemberController::class, 'update'])
            ->name('members.update')->middleware('permission:membership_new.members.edit');
        Route::delete('members/{member}', [MembershipNewMemberController::class, 'destroy'])
            ->name('members.destroy')->middleware('permission:membership_new.members.delete');

        // Membership plans.
        Route::get('plans', [MembershipNewPlanController::class, 'index'])
            ->name('plans.index')->middleware('permission:membership_new.plans.view');
        Route::get('plans/create', [MembershipNewPlanController::class, 'create'])
            ->name('plans.create')->middleware('permission:membership_new.plans.create');
        Route::post('plans', [MembershipNewPlanController::class, 'store'])
            ->name('plans.store')->middleware('permission:membership_new.plans.create');
        Route::get('plans/{membershipNewPlan}', [MembershipNewPlanController::class, 'show'])
            ->name('plans.show')->middleware('permission:membership_new.plans.view');
        Route::get('plans/{membershipNewPlan}/edit', [MembershipNewPlanController::class, 'edit'])
            ->name('plans.edit')->middleware('permission:membership_new.plans.edit');
        Route::put('plans/{membershipNewPlan}', [MembershipNewPlanController::class, 'update'])
            ->name('plans.update')->middleware('permission:membership_new.plans.edit');
        Route::delete('plans/{membershipNewPlan}', [MembershipNewPlanController::class, 'destroy'])
            ->name('plans.destroy')->middleware('permission:membership_new.plans.delete');

        // Membership payments.
        Route::get('payments', [MembershipNewPaymentController::class, 'index'])
            ->name('payments.index')->middleware('permission:membership_new.payments.view');
        Route::get('payments/create', [MembershipNewPaymentController::class, 'create'])
            ->name('payments.create')->middleware('permission:membership_new.payments.create');
        Route::post('payments', [MembershipNewPaymentController::class, 'store'])
            ->name('payments.store')->middleware('permission:membership_new.payments.create');
        Route::get('payments/{membershipNewPayment}', [MembershipNewPaymentController::class, 'show'])
            ->name('payments.show')->middleware('permission:membership_new.payments.view');
        Route::get('payments/{membershipNewPayment}/edit', [MembershipNewPaymentController::class, 'edit'])
            ->name('payments.edit')->middleware('permission:membership_new.payments.edit');
        Route::put('payments/{membershipNewPayment}', [MembershipNewPaymentController::class, 'update'])
            ->name('payments.update')->middleware('permission:membership_new.payments.edit');
        Route::delete('payments/{membershipNewPayment}', [MembershipNewPaymentController::class, 'destroy'])
            ->name('payments.destroy')->middleware('permission:membership_new.payments.delete');

        // Linked businesses/outlets.
        Route::get('linked-businesses', [MembershipNewLinkedBusinessController::class, 'index'])
            ->name('linked-businesses.index')->middleware('permission:membership_new.linked_businesses.view');
        Route::get('linked-businesses/create', [MembershipNewLinkedBusinessController::class, 'create'])
            ->name('linked-businesses.create')->middleware('permission:membership_new.linked_businesses.create');
        Route::post('linked-businesses', [MembershipNewLinkedBusinessController::class, 'store'])
            ->name('linked-businesses.store')->middleware('permission:membership_new.linked_businesses.create');
        Route::get('linked-businesses/{membershipNewLinkedBusiness}', [MembershipNewLinkedBusinessController::class, 'show'])
            ->name('linked-businesses.show')->middleware('permission:membership_new.linked_businesses.view');
        Route::get('linked-businesses/{membershipNewLinkedBusiness}/edit', [MembershipNewLinkedBusinessController::class, 'edit'])
            ->name('linked-businesses.edit')->middleware('permission:membership_new.linked_businesses.edit');
        Route::put('linked-businesses/{membershipNewLinkedBusiness}', [MembershipNewLinkedBusinessController::class, 'update'])
            ->name('linked-businesses.update')->middleware('permission:membership_new.linked_businesses.edit');
        Route::delete('linked-businesses/{membershipNewLinkedBusiness}', [MembershipNewLinkedBusinessController::class, 'destroy'])
            ->name('linked-businesses.destroy')->middleware('permission:membership_new.linked_businesses.delete');

        // Point rules and point ledger.
        Route::get('point-rules', [MembershipNewPointRuleController::class, 'index'])
            ->name('point-rules.index')->middleware('permission:membership_new.point_rules.view');
        Route::get('point-rules/create', [MembershipNewPointRuleController::class, 'create'])
            ->name('point-rules.create')->middleware('permission:membership_new.point_rules.create');
        Route::post('point-rules', [MembershipNewPointRuleController::class, 'store'])
            ->name('point-rules.store')->middleware('permission:membership_new.point_rules.create');
        Route::get('point-rules/{membershipNewPointRule}', [MembershipNewPointRuleController::class, 'show'])
            ->name('point-rules.show')->middleware('permission:membership_new.point_rules.view');
        Route::get('point-rules/{membershipNewPointRule}/edit', [MembershipNewPointRuleController::class, 'edit'])
            ->name('point-rules.edit')->middleware('permission:membership_new.point_rules.edit');
        Route::put('point-rules/{membershipNewPointRule}', [MembershipNewPointRuleController::class, 'update'])
            ->name('point-rules.update')->middleware('permission:membership_new.point_rules.edit');
        Route::delete('point-rules/{membershipNewPointRule}', [MembershipNewPointRuleController::class, 'destroy'])
            ->name('point-rules.destroy')->middleware('permission:membership_new.point_rules.delete');

        Route::get('points', [MembershipNewPointTransactionController::class, 'index'])
            ->name('points.index')->middleware('permission:membership_new.points.view');
        Route::get('points/member/{memberId}', [MembershipNewPointTransactionController::class, 'memberLedger'])
            ->name('points.member-ledger')->middleware('permission:membership_new.points.view');
        Route::post('points/earn', [MembershipNewPointTransactionController::class, 'earn'])
            ->name('points.earn')->middleware('permission:membership_new.points.earn');
        Route::post('points/redeem', [MembershipNewPointTransactionController::class, 'redeem'])
            ->name('points.redeem')->middleware('permission:membership_new.points.redeem');

        // Shares.
        Route::get('shares', [MembershipNewShareController::class, 'index'])
            ->name('shares.index')->middleware('permission:membership_new.shares.view');
        Route::get('shares/create', [MembershipNewShareController::class, 'create'])
            ->name('shares.create')->middleware('permission:membership_new.shares.create');
        Route::post('shares', [MembershipNewShareController::class, 'store'])
            ->name('shares.store')->middleware('permission:membership_new.shares.create');
        Route::get('shares/{membershipNewShareHolding}', [MembershipNewShareController::class, 'show'])
            ->name('shares.show')->middleware('permission:membership_new.shares.view');
        Route::get('shares/{membershipNewShareHolding}/edit', [MembershipNewShareController::class, 'edit'])
            ->name('shares.edit')->middleware('permission:membership_new.shares.edit');
        Route::put('shares/{membershipNewShareHolding}', [MembershipNewShareController::class, 'update'])
            ->name('shares.update')->middleware('permission:membership_new.shares.edit');
        Route::delete('shares/{membershipNewShareHolding}', [MembershipNewShareController::class, 'destroy'])
            ->name('shares.destroy')->middleware('permission:membership_new.shares.delete');

        // Dividends.
        Route::get('dividends', [MembershipNewDividendController::class, 'index'])
            ->name('dividends.index')->middleware('permission:membership_new.dividends.view');
        Route::get('dividends/create', [MembershipNewDividendController::class, 'create'])
            ->name('dividends.create')->middleware('permission:membership_new.dividends.create');
        Route::post('dividends', [MembershipNewDividendController::class, 'store'])
            ->name('dividends.store')->middleware('permission:membership_new.dividends.create');
        Route::get('dividends/{dividend}', [MembershipNewDividendController::class, 'show'])
            ->name('dividends.show')->middleware('permission:membership_new.dividends.view');
        Route::get('dividends/{dividend}/edit', [MembershipNewDividendController::class, 'edit'])
            ->name('dividends.edit')->middleware('permission:membership_new.dividends.edit');
        Route::put('dividends/{dividend}', [MembershipNewDividendController::class, 'update'])
            ->name('dividends.update')->middleware('permission:membership_new.dividends.edit');
        Route::delete('dividends/{dividend}', [MembershipNewDividendController::class, 'destroy'])
            ->name('dividends.destroy')->middleware('permission:membership_new.dividends.delete');
        Route::post('dividends/{dividend}/post', [MembershipNewDividendController::class, 'post'])
            ->name('dividends.post')->middleware('permission:membership_new.dividends.edit');

        // Cards.
        Route::post('members/{memberId}/card', [MembershipNewCardController::class, 'issue'])
            ->name('cards.issue')->middleware('permission:membership_new.cards.issue');
        Route::get('members/{memberId}/card', [MembershipNewCardController::class, 'print'])
            ->name('cards.print')->middleware('permission:membership_new.cards.print');

        Route::get('cards/scan-page', [MembershipNewCardScanController::class, 'page'])
            ->name('cards.scan-page')->middleware('permission:membership_new.cards.scan');
        Route::post('cards/lookup', [MembershipNewCardScanController::class, 'lookup'])
            ->name('cards.lookup')->middleware('permission:membership_new.cards.scan');

        // Customer sync.
        Route::prefix('customer-sync')->name('customer-sync.')
            ->middleware('permission:membership_new.customer_sync.view')
            ->group(function () {
                Route::get('/', [MembershipNewCustomerSyncController::class, 'index'])->name('index');
                Route::post('prepare-all', [MembershipNewCustomerSyncController::class, 'prepareAll'])
                    ->name('prepare-all')->middleware('permission:membership_new.customer_sync.run');
                Route::post('prepare-member', [MembershipNewCustomerSyncController::class, 'prepareMember'])
                    ->name('prepare-member')->middleware('permission:membership_new.customer_sync.run');
                Route::post('mark-synced/{mapId}', [MembershipNewCustomerSyncController::class, 'markSynced'])
                    ->name('mark-synced')->middleware('permission:membership_new.customer_sync.run');
            });

        // Redemption preview.
        Route::post('redemption/preview', [MembershipNewRedemptionController::class, 'preview'])
            ->name('redemption.preview')->middleware('permission:membership_new.points.redeem');

        // Reports.
        Route::prefix('reports')->name('reports.')
            ->middleware('permission:membership_new.reports.view')
            ->group(function () {
                Route::get('member-balances', [MembershipNewReportController::class, 'memberBalances'])->name('member-balances');
                Route::get('point-ledger', [MembershipNewReportController::class, 'pointLedger'])->name('point-ledger');
                Route::get('share-register', [MembershipNewReportController::class, 'shareRegister'])->name('share-register');
                Route::get('dividend-register', [MembershipNewReportController::class, 'dividendRegister'])->name('dividend-register');
            });

        Route::get('exports/{report}/csv', [MembershipNewExportController::class, 'csv'])
            ->name('exports.csv')->middleware('permission:membership_new.exports.download');
    });
