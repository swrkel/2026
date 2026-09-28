<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrMssController;

Route::prefix('hr-manager/mss')->middleware(['web','auth'])->name('hrmanager.mss.')->group(function(){
    Route::get('/', [HrMssController::class, 'index'])->name('index');
    Route::post('/team-members', [HrMssController::class, 'storeTeamMember'])->name('team_members.store');
    Route::post('/approvals', [HrMssController::class, 'storeApproval'])->name('approvals.store');
});

Route::prefix('hr/mss')->middleware(['web','auth'])->name('hr.mss.')->group(function(){
    Route::get('/', [HrMssController::class, 'index'])->name('dashboard');
    Route::post('/team-members', [HrMssController::class, 'storeTeamMember'])->name('team_members.store');
    Route::post('/approvals', [HrMssController::class, 'storeApproval'])->name('approvals.store');
});
