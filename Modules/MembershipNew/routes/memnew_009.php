<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewHealthController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('health', [MembershipNewHealthController::class, 'index'])
            ->name('health.index')
            ->middleware('permission:membership_new.health.view');
    });
