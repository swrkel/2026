<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Business\MyHealthAccessController;
use Modules\MyHealthMembers\Http\Controllers\Business\MyHealthMemberController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/members', [MyHealthMemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [MyHealthMemberController::class, 'create'])->name('members.create');
    Route::post('/members', [MyHealthMemberController::class, 'store'])->name('members.store');
    Route::get('/members/{member}', [MyHealthMemberController::class, 'show'])->name('members.show');
    Route::get('/members/{member}/edit', [MyHealthMemberController::class, 'edit'])->name('members.edit');
    Route::put('/members/{member}', [MyHealthMemberController::class, 'update'])->name('members.update');

    Route::post('/access/request/{member}', [MyHealthAccessController::class, 'requestPasscode'])->name('access.request');
    Route::post('/access/verify/{member}', [MyHealthAccessController::class, 'verifyPasscode'])->name('access.verify');
});
