<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Admin\EnterpriseSettingController;
use Modules\AirlineTicketingNew\Http\Controllers\Mobile\MobileActionController;

Route::get('admin/enterprise-settings',[EnterpriseSettingController::class,'index'])
    ->name('admin.enterprise-settings.index')
    ->middleware('permission:airline_ticketing_new.enterprise_settings.view');

Route::post('admin/enterprise-settings',[EnterpriseSettingController::class,'store'])
    ->name('admin.enterprise-settings.store')
    ->middleware('permission:airline_ticketing_new.enterprise_settings.manage');

Route::get('mobile/actions',[MobileActionController::class,'index'])
    ->name('mobile.actions')
    ->middleware('permission:airline_ticketing_new.mobile.view');
