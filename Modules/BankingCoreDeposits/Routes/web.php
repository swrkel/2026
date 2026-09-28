<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingCoreDeposits\Http\Controllers\AccountController;
use Modules\BankingCoreDeposits\Http\Controllers\DashboardController;
use Modules\BankingCoreDeposits\Http\Controllers\FixedDepositController;
use Modules\BankingCoreDeposits\Http\Controllers\InterestController;
use Modules\BankingCoreDeposits\Http\Controllers\ReportController;
use Modules\BankingCoreDeposits\Http\Controllers\TransactionController;
use Modules\BankingCoreDeposits\Http\Controllers\StandingInstructionController;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::resource('accounts', AccountController::class);
Route::post('accounts/{account}/freeze', [AccountController::class, 'freeze'])->name('accounts.freeze');
Route::post('accounts/{account}/unfreeze', [AccountController::class, 'unfreeze'])->name('accounts.unfreeze');
Route::post('accounts/{account}/close', [AccountController::class, 'close'])->name('accounts.close');
Route::post('accounts/{account}/reopen', [AccountController::class, 'reopen'])->name('accounts.reopen');
Route::resource('fixed-deposits', FixedDepositController::class);
Route::resource('transactions', TransactionController::class)->only(['index','create','store','show']);
Route::resource('standing-instructions', StandingInstructionController::class);
Route::get('interest/accrual-preview', [InterestController::class, 'accrualPreview'])->name('interest.accrual-preview');
Route::post('interest/post', [InterestController::class, 'post'])->name('interest.post');
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('deposit-register', [ReportController::class, 'depositRegister'])->name('deposit-register');
    Route::get('new-accounts', [ReportController::class, 'newAccounts'])->name('new-accounts');
    Route::get('closed-accounts', [ReportController::class, 'closedAccounts'])->name('closed-accounts');
    Route::get('dormant-accounts', [ReportController::class, 'dormantAccounts'])->name('dormant-accounts');
    Route::get('interest-accrual', [ReportController::class, 'interestAccrual'])->name('interest-accrual');
    Route::get('maturity-register', [ReportController::class, 'maturityRegister'])->name('maturity-register');
    Route::get('teller-summary', [ReportController::class, 'tellerSummary'])->name('teller-summary');
});
