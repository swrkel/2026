<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingMicrofinanceTreasury\Http\Controllers\TreasuryDashboardController;
use Modules\BankingMicrofinanceTreasury\Http\Controllers\VaultController;
use Modules\BankingMicrofinanceTreasury\Http\Controllers\InterBranchTransferController;
use Modules\BankingMicrofinanceTreasury\Http\Controllers\FundingLineController;
use Modules\BankingMicrofinanceTreasury\Http\Controllers\LiquidityController;
use Modules\BankingMicrofinanceTreasury\Http\Controllers\TreasuryReportController;

Route::middleware(['web','auth'])->prefix(config('bankingmicrofinancetreasury.route_prefix','banking/microfinance/treasury'))->name('bkg.mfi.treasury.')->group(function () {
    Route::get('/', [TreasuryDashboardController::class, 'index'])->name('dashboard');
    Route::resource('vaults', VaultController::class);
    Route::resource('inter-branch-transfers', InterBranchTransferController::class)->parameters(['inter-branch-transfers'=>'transfer']);
    Route::post('inter-branch-transfers/{transfer}/submit', [InterBranchTransferController::class, 'submit'])->name('transfers.submit');
    Route::post('inter-branch-transfers/{transfer}/approve', [InterBranchTransferController::class, 'approve'])->name('transfers.approve');
    Route::post('inter-branch-transfers/{transfer}/post', [InterBranchTransferController::class, 'post'])->name('transfers.post');
    Route::resource('funding-lines', FundingLineController::class)->parameters(['funding-lines'=>'fundingLine']);
    Route::get('liquidity/position', [LiquidityController::class, 'position'])->name('liquidity.position');
    Route::get('liquidity/gap-analysis', [LiquidityController::class, 'gapAnalysis'])->name('liquidity.gap');
    Route::get('reports/daily-liquidity', [TreasuryReportController::class, 'dailyLiquidity'])->name('reports.daily-liquidity');
    Route::get('reports/vault-movement', [TreasuryReportController::class, 'vaultMovement'])->name('reports.vault-movement');
    Route::get('reports/funding-utilization', [TreasuryReportController::class, 'fundingUtilization'])->name('reports.funding-utilization');
});
