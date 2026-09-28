<?php
use Illuminate\Support\Facades\Route;
use Modules\Tailoring\Operations\Http\Controllers\TailoringProductionCentreController;
use Modules\Tailoring\Operations\Http\Controllers\TailoringWorkshopController;
use Modules\Tailoring\Operations\Http\Controllers\TailoringMaterialCentreController;
use Modules\Tailoring\Operations\Http\Controllers\TailoringTrialCentreController;
use Modules\Tailoring\Operations\Http\Controllers\TailoringAlterationCentreController;
use Modules\Tailoring\Operations\Http\Controllers\TailoringDeliveryCentreController;
use Modules\Tailoring\Operations\Http\Controllers\TailoringProductionReportController;
Route::middleware(['web','auth'])->prefix('tailoring')->group(function(){
Route::get('production-centre',[TailoringProductionCentreController::class,'index'])->name('tailoring.production_centre.index');
Route::get('workshop',[TailoringWorkshopController::class,'index'])->name('tailoring.workshop.index');
Route::get('material-centre',[TailoringMaterialCentreController::class,'index'])->name('tailoring.material_centre.index');
Route::get('trial-centre',[TailoringTrialCentreController::class,'index'])->name('tailoring.trial_centre.index');
Route::get('alteration-centre',[TailoringAlterationCentreController::class,'index'])->name('tailoring.alteration_centre.index');
Route::get('delivery-centre',[TailoringDeliveryCentreController::class,'index'])->name('tailoring.delivery_centre.index');
Route::get('operations-reports',[TailoringProductionReportController::class,'index'])->name('tailoring.operations_reports.index');
});
