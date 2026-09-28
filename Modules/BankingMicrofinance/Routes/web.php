<?php
use Illuminate\Support\Facades\Route;
use Modules\BankingMicrofinance\Http\Controllers\CollectionCaseController;
use Modules\BankingMicrofinance\Http\Controllers\CollectionActionController;
use Modules\BankingMicrofinance\Http\Controllers\PromiseToPayController;
use Modules\BankingMicrofinance\Http\Controllers\RecoveryCaseController;
use Modules\BankingMicrofinance\Http\Controllers\LegalActionController;
use Modules\BankingMicrofinance\Http\Controllers\RepossessionController;
use Modules\BankingMicrofinance\Http\Controllers\CollectionReportController;
Route::middleware(['web','auth'])->prefix('banking/microfinance/collections')->name('bkg.mfi.collections.')->group(function(){
 Route::resource('cases', CollectionCaseController::class); Route::resource('actions', CollectionActionController::class); Route::resource('promises-to-pay', PromiseToPayController::class); Route::resource('recovery-cases', RecoveryCaseController::class); Route::resource('legal-actions', LegalActionController::class); Route::resource('repossessions', RepossessionController::class); Route::get('reports/dashboard',[CollectionReportController::class,'index'])->name('reports.dashboard');
});
