<?php
use Illuminate\Support\Facades\Route;
use Modules\AutoService\Http\Controllers\ServicePackageController;
use Modules\AutoService\Http\Controllers\ServicePackageReportController;
use Modules\AutoService\Http\Controllers\InvoiceController;

Route::prefix('auto-service')->as('autoservice.')->middleware(['web','auth'])->group(function(){
    Route::post('package-categories',[ServicePackageController::class,'storeCategory'])->name('package_categories.store');
    Route::get('packages/{package}/payload',[ServicePackageController::class,'payload'])->name('packages.payload');
    Route::post('invoices/{invoice}/post',[InvoiceController::class,'post'])->name('invoices.post');

    Route::get('reports/service-package-usage',[ServicePackageReportController::class,'usage'])->name('reports.package_usage');
    Route::get('reports/service-package-profitability',[ServicePackageReportController::class,'profitability'])->name('reports.package_profitability');
    Route::get('reports/service-package-stock-consumption',[ServicePackageReportController::class,'stockConsumption'])->name('reports.package_stock_consumption');
});
