<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('closing-balance-report')->group(function () {
    // API routes for ClosingBalanceReport can be placed here if needed.
});
