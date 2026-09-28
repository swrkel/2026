<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessApiController;

Route::prefix('api/identity-access')->as('identityaccess.api.')->middleware(['api'])->group(function () {
    Route::post('/authenticate', [IdentityAccessApiController::class, 'authenticate'])->name('authenticate');
    Route::post('/verify-otp', [IdentityAccessApiController::class, 'verifyOtp'])->name('verify_otp');
    Route::post('/logout', [IdentityAccessApiController::class, 'logout'])->name('logout');
});
