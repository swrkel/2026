<?php
use Illuminate\Support\Facades\Route;
use Modules\BankingCRM\Http\Controllers\CrmDashboardController;
use Modules\BankingCRM\Http\Controllers\Customer360Controller;
use Modules\BankingCRM\Http\Controllers\RelationshipManagerController;
use Modules\BankingCRM\Http\Controllers\CustomerInteractionController;
use Modules\BankingCRM\Http\Controllers\ServiceRequestController;
use Modules\BankingCRM\Http\Controllers\ComplaintController;
use Modules\BankingCRM\Http\Controllers\CampaignController;
use Modules\BankingCRM\Http\Controllers\CrmReportController;
use Modules\BankingCRM\Http\Controllers\CrmSettingController;

Route::prefix('banking/crm')->name('bankingcrm.')->group(function () {
    Route::get('/', [CrmDashboardController::class, 'index'])->name('dashboard');
    Route::get('/customers', [Customer360Controller::class, 'index'])->name('customers');
    Route::get('/relationships', [RelationshipManagerController::class, 'index'])->name('relationships');
    Route::get('/interactions', [CustomerInteractionController::class, 'index'])->name('interactions');
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service_requests');
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints');
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::get('/reports', [CrmReportController::class, 'index'])->name('reports');
    Route::get('/settings', [CrmSettingController::class, 'index'])->name('settings');
});
