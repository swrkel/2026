<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        // MEMNEW_010 contains docs/contracts/examples only.
        // No runtime routes required in this package.
    });
