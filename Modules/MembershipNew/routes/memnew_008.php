<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewAuditController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewApprovalController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewBusinessAccessController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewCardLifecycleController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('audit', [MembershipNewAuditController::class, 'index'])
            ->name('audit.index')
            ->middleware('permission:membership_new.audit.view');

        Route::get('approvals', [MembershipNewApprovalController::class, 'index'])
            ->name('approvals.index')
            ->middleware('permission:membership_new.approvals.view');

        Route::post('approvals', [MembershipNewApprovalController::class, 'store'])
            ->name('approvals.store')
            ->middleware('permission:membership_new.approvals.create');

        Route::post('approvals/{requestId}/approve', [MembershipNewApprovalController::class, 'approve'])
            ->name('approvals.approve')
            ->middleware('permission:membership_new.approvals.approve');

        Route::post('approvals/{requestId}/reject', [MembershipNewApprovalController::class, 'reject'])
            ->name('approvals.reject')
            ->middleware('permission:membership_new.approvals.reject');

        Route::get('business-access', [MembershipNewBusinessAccessController::class, 'edit'])
            ->name('business-access.edit')
            ->middleware('permission:membership_new.business_access.edit');

        Route::post('business-access', [MembershipNewBusinessAccessController::class, 'update'])
            ->name('business-access.update')
            ->middleware('permission:membership_new.business_access.edit');

        Route::get('card-lifecycle', [MembershipNewCardLifecycleController::class, 'index'])
            ->name('card-lifecycle.index')
            ->middleware('permission:membership_new.cards.issue');

        Route::post('card-lifecycle/{cardId}/block', [MembershipNewCardLifecycleController::class, 'block'])
            ->name('card-lifecycle.block')
            ->middleware('permission:membership_new.cards.issue');

        Route::post('card-lifecycle/{cardId}/replace', [MembershipNewCardLifecycleController::class, 'replace'])
            ->name('card-lifecycle.replace')
            ->middleware('permission:membership_new.cards.issue');
    });
