<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewHandoverController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('handover', [MembershipNewHandoverController::class, 'index'])
            ->name('handover.index')
            ->middleware('permission:membership_new.health.view');
    });
