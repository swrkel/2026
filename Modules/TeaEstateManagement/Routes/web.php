<?php
use Illuminate\Support\Facades\Route;
use Modules\TeaEstateManagement\Http\Controllers\{DashboardController,PlantationController,HarvestController,PartyController,BuyingController,ProcessingController,InventoryController,SalesController,FinanceController,ReportsController,SettingsController};

$prefix=config('teaestatemanagement.route_prefix','tea-estate-management');
Route::group(['prefix'=>$prefix,'middleware'=>['web','auth','SetSessionData','language','timezone','tenant.context','check.route.permission','can:tea_estate.access']],function(){
 Route::get('/',[DashboardController::class,'index'])->middleware('can:tea_estate.dashboard.view')->name('teaestate.dashboard');
 Route::get('/plantation',[PlantationController::class,'index'])->middleware('can:tea_estate.plantation.view')->name('teaestate.plantation.index');
 Route::post('/plantation/estates',[PlantationController::class,'storeEstate'])->middleware('can:tea_estate.plantation.manage')->name('teaestate.plantation.estates.store');
 Route::post('/plantation/fields',[PlantationController::class,'storeField'])->middleware('can:tea_estate.plantation.manage')->name('teaestate.plantation.fields.store');
 Route::post('/plantation/activities',[PlantationController::class,'storeActivity'])->middleware('can:tea_estate.plantation.manage')->name('teaestate.plantation.activities.store');
 Route::get('/harvests',[HarvestController::class,'index'])->middleware('can:tea_estate.harvests.view')->name('teaestate.harvests.index');
 Route::post('/harvests',[HarvestController::class,'store'])->middleware('can:tea_estate.harvests.create')->name('teaestate.harvests.store');
 Route::get('/parties',[PartyController::class,'index'])->middleware('can:tea_estate.parties.view')->name('teaestate.parties.index');
 Route::post('/parties',[PartyController::class,'store'])->middleware('can:tea_estate.parties.manage')->name('teaestate.parties.store');
 Route::get('/buying',[BuyingController::class,'index'])->middleware('can:tea_estate.buying.view')->name('teaestate.buying.index');
 Route::post('/buying',[BuyingController::class,'store'])->middleware('can:tea_estate.buying.create')->name('teaestate.buying.store');
 Route::post('/buying/payment',[BuyingController::class,'pay'])->middleware('can:tea_estate.buying.pay')->name('teaestate.buying.pay');
 Route::get('/processing',[ProcessingController::class,'index'])->middleware('can:tea_estate.processing.view')->name('teaestate.processing.index');
 Route::post('/processing',[ProcessingController::class,'store'])->middleware('can:tea_estate.processing.create')->name('teaestate.processing.store');
 Route::post('/processing/stage',[ProcessingController::class,'stage'])->middleware('can:tea_estate.processing.update')->name('teaestate.processing.stage');
 Route::post('/processing/finalize',[ProcessingController::class,'finalize'])->middleware('can:tea_estate.processing.finalize')->name('teaestate.processing.finalize');
 Route::get('/inventory',[InventoryController::class,'index'])->middleware('can:tea_estate.inventory.view')->name('teaestate.inventory.index');
 Route::get('/sales',[SalesController::class,'index'])->middleware('can:tea_estate.sales.view')->name('teaestate.sales.index');
 Route::post('/sales',[SalesController::class,'store'])->middleware('can:tea_estate.sales.create')->name('teaestate.sales.store');
 Route::post('/sales/receipt',[SalesController::class,'receive'])->middleware('can:tea_estate.sales.receive')->name('teaestate.sales.receive');
 Route::get('/finance',[FinanceController::class,'index'])->middleware('can:tea_estate.finance.view')->name('teaestate.finance.index');
 Route::post('/finance/mappings',[FinanceController::class,'saveMappings'])->middleware('can:tea_estate.finance.manage')->name('teaestate.finance.mappings');
 Route::post('/finance/retry',[FinanceController::class,'retry'])->middleware('can:tea_estate.finance.manage')->name('teaestate.finance.retry');
 Route::get('/reports',[ReportsController::class,'index'])->middleware('can:tea_estate.reports.view')->name('teaestate.reports.index');
 Route::get('/settings',[SettingsController::class,'index'])->middleware('can:tea_estate.settings.manage')->name('teaestate.settings.index');
 Route::post('/settings/factory',[SettingsController::class,'factory'])->middleware('can:tea_estate.settings.manage')->name('teaestate.settings.factory');
 Route::post('/settings/grade',[SettingsController::class,'grade'])->middleware('can:tea_estate.settings.manage')->name('teaestate.settings.grade');
 Route::post('/settings/stage',[SettingsController::class,'stage'])->middleware('can:tea_estate.settings.manage')->name('teaestate.settings.stage');
});
