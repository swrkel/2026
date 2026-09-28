<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingInternetBanking\Http\Controllers\InternetBankingDashboardController;
use Modules\BankingInternetBanking\Http\Controllers\CustomerAccessController;
use Modules\BankingInternetBanking\Http\Controllers\AccountServiceController;
use Modules\BankingInternetBanking\Http\Controllers\BeneficiaryController;
use Modules\BankingInternetBanking\Http\Controllers\TransferController;
use Modules\BankingInternetBanking\Http\Controllers\BillPaymentController;
use Modules\BankingInternetBanking\Http\Controllers\SecureMessageController;
use Modules\BankingInternetBanking\Http\Controllers\SecurityCenterController;
use Modules\BankingInternetBanking\Http\Controllers\InternetBankingReportController;
use Modules\BankingInternetBanking\Http\Controllers\InternetBankingAdminController;

Route::middleware(['web', 'auth'])->prefix('banking/internet')->name('banking.internet.')->group(function () {
    Route::get('/', [InternetBankingDashboardController::class, 'index'])->name('index');
    Route::get('/access', [CustomerAccessController::class, 'index'])->name('access.index');
    Route::get('/accounts', [AccountServiceController::class, 'index'])->name('accounts.index');
    Route::get('/beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/bill-payments', [BillPaymentController::class, 'index'])->name('bills.index');
    Route::get('/secure-messages', [SecureMessageController::class, 'index'])->name('messages.index');
    Route::get('/security-centre', [SecurityCenterController::class, 'index'])->name('security.index');
    Route::get('/reports', [InternetBankingReportController::class, 'index'])->name('reports.index');
    Route::get('/admin', [InternetBankingAdminController::class, 'index'])->name('admin.index');
});
