<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Admin\FeatureSwitchController;
Route::get('admin/feature-switches',[FeatureSwitchController::class,'index'])->name('admin.feature-switches.index')->middleware('permission:airline_ticketing_new.admin.features');
Route::put('admin/feature-switches/{featureSwitch}',[FeatureSwitchController::class,'update'])->name('admin.feature-switches.update')->middleware('permission:airline_ticketing_new.admin.features');
