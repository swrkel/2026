<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberSelfRegistrationController;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberLoginController;
use Modules\MyHealthMembers\Http\Controllers\Portal\MyHealthMemberPortalController;
use Modules\MyHealthMembers\Http\Middleware\MyHealthMemberPortalAuth;

/*
|--------------------------------------------------------------------------
| My Health Public Routes
|--------------------------------------------------------------------------
| Public registration and member portal authentication remain inside the
| MyHealthMembers module. ERP routes only need to load this file.
*/


// Canonical public My Health member login URLs. These are intentionally short and stable.
Route::get('/myhealth', [MemberLoginController::class, 'create'])->name('myhealth.public.login.create');
Route::get('/myhealth/login', [MemberLoginController::class, 'create'])->name('myhealth.public.login.legacy-create');
Route::post('/myhealth/login', [MemberLoginController::class, 'login'])->name('myhealth.public.login.submit');
Route::get('/myhealth/otp', [MemberLoginController::class, 'otpForm'])->name('myhealth.public.login.otp.create');
Route::post('/myhealth/otp', [MemberLoginController::class, 'verifyOtp'])->name('myhealth.public.login.otp.verify');
Route::post('/myhealth/otp/resend', [MemberLoginController::class, 'resendOtp'])->name('myhealth.public.login.otp.resend');
Route::post('/myhealth/logout', [MemberLoginController::class, 'logout'])->name('myhealth.public.logout');

Route::get('/patient/login', function () { return redirect('/myhealth'); })->name('myhealth.public.patient-login-redirect');

Route::post('/myhealth-register', [MemberSelfRegistrationController::class, 'store'])->name('myhealth-register');
Route::get('/myhealth-register', [MemberSelfRegistrationController::class, 'create'])->name('myhealth-register.create');

Route::prefix('myhealth-register')->as('myhealth.public.')->group(function () {
    Route::get('/', [MemberSelfRegistrationController::class, 'create'])->name('register.create');
    Route::post('/', [MemberSelfRegistrationController::class, 'store'])->name('register.store');
});

Route::prefix('my-health-member')->as('myhealth.public.member-alias.')->group(function () {
    Route::get('/login', [MemberLoginController::class, 'create'])->name('login.create');
    Route::post('/login', [MemberLoginController::class, 'login'])->name('login.submit');
    Route::get('/otp', [MemberLoginController::class, 'otpForm'])->name('login.otp.create');
    Route::post('/otp', [MemberLoginController::class, 'verifyOtp'])->name('login.otp.verify');
    Route::post('/otp/resend', [MemberLoginController::class, 'resendOtp'])->name('login.otp.resend');
    Route::post('/logout', [MemberLoginController::class, 'logout'])->name('logout');
});

// Backward-compatible old login URLs.
Route::prefix('myhealth-register')->as('myhealth.public.')->group(function () {
    Route::get('/login', [MemberLoginController::class, 'create'])->name('legacy.login.create');
    Route::post('/login', [MemberLoginController::class, 'login'])->name('legacy.login.submit');
});

Route::get('/myhealth-member-dashboard', [MyHealthMemberPortalController::class, 'dashboard'])
    ->middleware(MyHealthMemberPortalAuth::class)
    ->name('myhealth.public.dashboard');

Route::prefix('my-health-member/portal')
    ->as('myhealth.member.portal.')
    ->middleware(MyHealthMemberPortalAuth::class)
    ->group(function () {
        Route::get('/', [MyHealthMemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [MyHealthMemberPortalController::class, 'profile'])->name('profile');
        Route::get('/medical-history', [MyHealthMemberPortalController::class, 'history'])->name('history');
        Route::get('/prescriptions', [MyHealthMemberPortalController::class, 'prescriptions'])->name('prescriptions');
        Route::get('/laboratory', [MyHealthMemberPortalController::class, 'labs'])->name('labs');
        Route::get('/radiology', [MyHealthMemberPortalController::class, 'radiology'])->name('radiology');
        Route::get('/vaccinations', [MyHealthMemberPortalController::class, 'vaccinations'])->name('vaccinations');
        Route::get('/billing', [MyHealthMemberPortalController::class, 'billing'])->name('billing');
        Route::get('/appointments', [MyHealthMemberPortalController::class, 'appointments'])->name('appointments');
        Route::post('/appointments/request', [MyHealthMemberPortalController::class, 'requestAppointment'])->name('appointments.request');
        Route::get('/documents', [MyHealthMemberPortalController::class, 'documents'])->name('documents');
        Route::get('/documents/{id}/download', [MyHealthMemberPortalController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/timeline', [MyHealthMemberPortalController::class, 'timeline'])->name('timeline');
        Route::get('/notifications', [MyHealthMemberPortalController::class, 'notifications'])->name('notifications');
        Route::get('/settings', [MyHealthMemberPortalController::class, 'settings'])->name('settings');
        Route::post('/settings/passcode', [MyHealthMemberPortalController::class, 'changePasscode'])->name('settings.passcode');
    });
