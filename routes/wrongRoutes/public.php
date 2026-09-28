<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberSelfRegistrationController;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberLoginController;

Route::prefix('myhealth-register')->as('myhealth.public.')->group(function () {
    Route::get('/', [MemberSelfRegistrationController::class, 'create'])->name('register.create');
    Route::post('/', [MemberSelfRegistrationController::class, 'store'])->name('register.store');

    Route::get('/login', [MemberLoginController::class, 'create'])->name('login.create');
    Route::post('/login', [MemberLoginController::class, 'login'])->name('login.submit');
    Route::get('/dashboard', [MemberLoginController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [MemberLoginController::class, 'logout'])->name('logout');
});
