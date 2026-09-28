<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewDemoController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('demo', [MembershipNewDemoController::class, 'index'])
            ->name('demo.index')
            ->middleware('permission:membership_new.health.view');
    });
