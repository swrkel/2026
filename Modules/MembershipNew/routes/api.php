<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('membership-new/api')
    ->name('membership-new.api.')
    ->group(function () {
        // Future standalone API endpoints for Membership-New only.
    });
