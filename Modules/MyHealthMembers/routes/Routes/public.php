<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberSelfRegistrationController;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberLoginController;

/*
|--------------------------------------------------------------------------
| My Health Public Routes
|--------------------------------------------------------------------------
| These routes are intentionally kept outside the authenticated ERP area.
| The simple route name "myhealth-register" is required by the existing
| signup popup / login page JavaScript. Do not remove it.
*/

// Compatibility route used by the signup popup form / AJAX submit.
Route::post('/myhealth-register', [MemberSelfRegistrationController::class, 'store'])
    ->name('myhealth-register');

// Compatibility route for opening the registration page directly.
Route::get('/myhealth-register', [MemberSelfRegistrationController::class, 'create'])
    ->name('myhealth-register.create');

Route::prefix('myhealth-register')->as('myhealth.public.')->group(function () {
    Route::get('/', [MemberSelfRegistrationController::class, 'create'])->name('register.create');
    Route::post('/', [MemberSelfRegistrationController::class, 'store'])->name('register.store');

    Route::get('/login', [MemberLoginController::class, 'create'])->name('login.create');
    Route::post('/login', [MemberLoginController::class, 'login'])->name('login.submit');
    Route::get('/dashboard', [MemberLoginController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [MemberLoginController::class, 'logout'])->name('logout');
});
