<?php
use Illuminate\Support\Facades\Route;
use Modules\RiceMill\Http\Controllers\{DashboardController,SettingsController,PaddyPurchaseController,PaddyReceiptController,PaddyStockController,ProductionController,PackingController,PackagingMaterialController,PackagingMaterialMappingController,FinishedStockController,DispatchController,ReportController,WeighbridgeController,ByproductController};
use Modules\RiceMill\Http\Controllers\Dashboard\DashboardLoginController;
use Modules\RiceMill\Http\Controllers\Dashboard\DashboardPortalController;
use Modules\RiceMill\Http\Middleware\EnsureRiceMillDashboardSession;


Route::middleware(['web','tenant.context'])->prefix('rice-mill-dashboard')->name('rice-mill-dashboard.')->group(function () {
    Route::get('/login', [DashboardLoginController::class, 'show'])->name('login');
    Route::post('/login', [DashboardLoginController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
});

Route::middleware(['web','tenant.context','auth','SetSessionData','language','timezone',EnsureRiceMillDashboardSession::class])
    ->prefix('rice-mill-dashboard')->name('rice-mill-dashboard.')->group(function () {
        Route::get('/', [DashboardPortalController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardPortalController::class, 'index'])->name('dashboard.index');
        Route::post('/logout', [DashboardLoginController::class, 'logout'])->name('logout');
    });

Route::middleware(config('ricemill.middleware',['web','tenant.context','auth','rcm.context']))->prefix(config('ricemill.route_prefix','rice-mill'))->name('rice-mill.')->group(function(){
 Route::get('/',[DashboardController::class,'index'])->middleware('rcm.permission:rice_mill.dashboard.view')->name('dashboard');
 Route::get('/settings',[SettingsController::class,'index'])->middleware('rcm.permission:rice_mill.settings.view')->name('settings.index');
 Route::post('/settings',[SettingsController::class,'save'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.save');
 Route::post('/settings/product-category-mapping',[SettingsController::class,'saveProductCategoryMapping'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.product-category-mapping.save');
Route::post('/settings/packaging-material-product-selection',[SettingsController::class,'savePackagingMaterialProductSelection'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.packaging-material-product-selection.save');
Route::post('/settings/output-type-product-selection',[SettingsController::class,'saveOutputTypeProductSelection'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.output-type-product-selection.save');
Route::post('/settings/product-category-mapping/{type}/toggle',[SettingsController::class,'toggleProductCategoryMapping'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.product-category-mapping.toggle');
 Route::post('/settings/receive-paddy',[SettingsController::class,'saveReceivePaddy'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.receive-paddy.save');
 Route::post('/settings/sales-approval',[SettingsController::class,'saveSalesApproval'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.sales-approval.save');
 Route::post('/settings/receive-paddy/numbering',[SettingsController::class,'updateReceivePaddyNumbering'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.receive-paddy.numbering.update');

 Route::post('/settings/variety',[SettingsController::class,'variety'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.variety');
 Route::put('/settings/variety/{id}',[SettingsController::class,'updateVariety'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.variety.update');
 Route::delete('/settings/variety/{id}',[SettingsController::class,'deleteVariety'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.variety.delete');
 Route::post('/settings/variety/{id}/toggle',[SettingsController::class,'toggleVariety'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.variety.toggle');

 Route::post('/settings/mill',[SettingsController::class,'mill'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.mill');
 Route::put('/settings/mill/{id}',[SettingsController::class,'updateMill'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.mill.update');
 Route::delete('/settings/mill/{id}',[SettingsController::class,'deleteMill'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.mill.delete');
 Route::post('/settings/mill/{id}/toggle',[SettingsController::class,'toggleMill'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.mill.toggle');

 Route::post('/settings/product',[SettingsController::class,'product'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.product');
 Route::put('/settings/product/{id}',[SettingsController::class,'updateProduct'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.product.update');
 Route::delete('/settings/product/{id}',[SettingsController::class,'deleteProduct'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.product.delete');
 Route::post('/settings/product/{id}/toggle',[SettingsController::class,'toggleProduct'])->middleware('rcm.permission:rice_mill.settings.edit')->name('settings.product.toggle');
 Route::get('/purchases',[PaddyPurchaseController::class,'index'])->middleware('rcm.permission:rice_mill.paddy_purchase.view')->name('purchases.index');
 Route::get('/purchases/create',[PaddyPurchaseController::class,'create'])->middleware('rcm.permission:rice_mill.paddy_purchase.create')->name('purchases.create');
 Route::post('/purchases',[PaddyPurchaseController::class,'store'])->middleware('rcm.permission:rice_mill.paddy_purchase.create')->name('purchases.store');
 Route::post('/purchases/{id}/approve',[PaddyPurchaseController::class,'approve'])->middleware('rcm.permission:rice_mill.paddy_purchase.approve')->name('purchases.approve');
 Route::get('/weighbridge',[WeighbridgeController::class,'index'])->middleware('rcm.permission:rice_mill.paddy_receipt.view')->name('weighbridge.index');
 Route::get('/receipts',[PaddyReceiptController::class,'index'])->middleware('rcm.permission:rice_mill.paddy_receipt.view')->name('receipts.index');
 Route::get('/receipts/create',[PaddyReceiptController::class,'create'])->middleware('rcm.permission:rice_mill.paddy_receipt.create')->name('receipts.create');
 Route::get('/receipts/purchase-details/{id}',[PaddyReceiptController::class,'purchaseDetails'])->middleware('rcm.permission:rice_mill.paddy_receipt.create')->name('receipts.purchase-details');
 Route::post('/receipts',[PaddyReceiptController::class,'store'])->middleware('rcm.permission:rice_mill.paddy_receipt.create')->name('receipts.store');
 Route::get('/paddy-stock',[PaddyStockController::class,'index'])->middleware('rcm.permission:rice_mill.paddy_stock.view')->name('paddy-stock.index');
 Route::get('/paddy-stock/{id}/ledger',[PaddyStockController::class,'ledger'])->middleware('rcm.permission:rice_mill.paddy_stock.view')->name('paddy-stock.ledger');
 Route::post('/paddy-stock/{id}/adjust',[PaddyStockController::class,'adjust'])->middleware('rcm.permission:rice_mill.paddy_stock.adjust')->name('paddy-stock.adjust');
 Route::get('/production',[ProductionController::class,'index'])->middleware('rcm.permission:rice_mill.production.view')->name('production.index');
 Route::get('/production/operation',[ProductionController::class,'entry'])->middleware('rcm.permission:rice_mill.production.view')->name('production.entry');
 Route::get('/production/create',[ProductionController::class,'create'])->middleware(['rcm.permission:rice_mill.production.create','rcm.permission:rice_mill.production.complete'])->name('production.create');
 Route::post('/production',[ProductionController::class,'store'])->middleware(['rcm.permission:rice_mill.production.create','rcm.permission:rice_mill.production.complete'])->name('production.store');
 Route::get('/production/{id}',[ProductionController::class,'show'])->where('id','[0-9]+')->middleware('rcm.permission:rice_mill.production.view')->name('production.show');
 Route::get('/production/{id}/complete',[ProductionController::class,'completeForm'])->middleware('rcm.permission:rice_mill.production.complete')->name('production.complete-form');
 Route::post('/production/{id}/complete',[ProductionController::class,'complete'])->middleware('rcm.permission:rice_mill.production.complete')->name('production.complete');
 Route::get('/byproducts',[ByproductController::class,'index'])->middleware('rcm.permission:rice_mill.production.view')->name('byproducts.index');
 Route::get('/byproducts/{type}/ledger',[ByproductController::class,'ledger'])->middleware('rcm.permission:rice_mill.production.view')->name('byproducts.ledger');
 Route::post('/byproducts/{type}/adjust',[ByproductController::class,'adjust'])->middleware('rcm.permission:rice_mill.finished_stock.adjust')->name('byproducts.adjust');
 Route::get('/packing',[PackingController::class,'index'])->middleware('rcm.permission:rice_mill.packing.view')->name('packing.index');
 Route::get('/packing/create',[PackingController::class,'create'])->middleware('rcm.permission:rice_mill.packing.create')->name('packing.create');
 Route::post('/packing',[PackingController::class,'store'])->middleware('rcm.permission:rice_mill.packing.create')->name('packing.store');
 Route::get('/packaging-materials',[PackagingMaterialController::class,'index'])->middleware('rcm.permission:rice_mill.packing.view')->name('packaging-materials.index');
 Route::post('/packaging-materials',[PackagingMaterialController::class,'store'])->middleware('rcm.permission:rice_mill.packing.create')->name('packaging-materials.store');
 Route::post('/packaging-materials/{id}/toggle',[PackagingMaterialController::class,'toggle'])->middleware('rcm.permission:rice_mill.packing.create')->name('packaging-materials.toggle');
 Route::get('/packaging-materials/{id}/ledger',[PackagingMaterialController::class,'ledger'])->middleware('rcm.permission:rice_mill.packing.view')->name('packaging-materials.ledger');
 Route::post('/packaging-materials/{id}/adjust',[PackagingMaterialController::class,'adjust'])->middleware('rcm.permission:rice_mill.packing.create')->name('packaging-materials.adjust');
 Route::get('/packaging-material-mappings',[PackagingMaterialMappingController::class,'index'])->middleware('rcm.permission:rice_mill.packing.view')->name('packaging-material-mappings.index');
 Route::post('/packaging-material-mappings',[PackagingMaterialMappingController::class,'store'])->middleware('rcm.permission:rice_mill.packing.create')->name('packaging-material-mappings.store');
 Route::delete('/packaging-material-mappings/{id}',[PackagingMaterialMappingController::class,'destroy'])->middleware('rcm.permission:rice_mill.packing.create')->name('packaging-material-mappings.destroy');
 Route::get('/finished-stock',[FinishedStockController::class,'index'])->middleware('rcm.permission:rice_mill.finished_stock.view')->name('finished-stock.index');
 Route::get('/finished-stock/{id}/ledger',[FinishedStockController::class,'ledger'])->middleware('rcm.permission:rice_mill.finished_stock.view')->name('finished-stock.ledger');
 Route::post('/finished-stock/{id}/adjust',[FinishedStockController::class,'adjust'])->middleware('rcm.permission:rice_mill.finished_stock.adjust')->name('finished-stock.adjust');
 Route::get('/dispatch',[DispatchController::class,'index'])->middleware('rcm.permission:rice_mill.dispatch.view')->name('dispatch.index');
 Route::get('/dispatch/create',[DispatchController::class,'create'])->middleware('rcm.permission:rice_mill.dispatch.create')->name('dispatch.create');
 Route::post('/dispatch',[DispatchController::class,'preview'])->middleware('rcm.permission:rice_mill.dispatch.create')->name('dispatch.store');
 Route::post('/dispatch/preview/{token}/save',[DispatchController::class,'savePreview'])->middleware('rcm.permission:rice_mill.dispatch.create')->name('dispatch.preview.save');
 Route::post('/dispatch/preview/{token}/approve',[DispatchController::class,'approvePreview'])->middleware(['rcm.permission:rice_mill.dispatch.create','rcm.permission:rice_mill.dispatch.approve'])->name('dispatch.preview.approve');
 Route::get('/dispatch/{id}',[DispatchController::class,'show'])->where('id','[0-9]+')->middleware('rcm.permission:rice_mill.dispatch.view')->name('dispatch.show');
 Route::post('/dispatch/{id}/approve',[DispatchController::class,'approve'])->where('id','[0-9]+')->middleware('rcm.permission:rice_mill.dispatch.approve')->name('dispatch.approve');
 Route::get('/reports',[ReportController::class,'index'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.index');
 Route::get('/reports/purchases',[ReportController::class,'purchases'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.purchases');
 Route::get('/reports/receipts',[ReportController::class,'receipts'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.receipts');
 Route::get('/reports/paddy-stock',[ReportController::class,'paddyStock'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.paddy-stock');
 Route::get('/reports/production',[ReportController::class,'production'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.production');
 Route::get('/reports/finished-stock',[ReportController::class,'finishedStock'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.finished-stock');
 Route::get('/reports/dispatch',[ReportController::class,'dispatch'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.dispatch');
 Route::get('/reports/profitability',[ReportController::class,'profitability'])->middleware('rcm.permission:rice_mill.reports.view')->name('reports.profitability');
});
