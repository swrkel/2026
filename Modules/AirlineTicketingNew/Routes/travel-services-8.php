<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Ancillary\AncillaryServiceController;
use Modules\AirlineTicketingNew\Http\Controllers\Bundles\ServiceBundleController;
use Modules\AirlineTicketingNew\Http\Controllers\Insurance\InsurancePolicyController;
use Modules\AirlineTicketingNew\Http\Controllers\Visa\VisaStatusController;

Route::post('visa/applications/{application}/status', [VisaStatusController::class, 'update'])
    ->name('visa.applications.status')
    ->middleware('permission:airline_ticketing_new.visa_applications.status');

Route::get('insurance/policies', [InsurancePolicyController::class, 'index'])
    ->name('insurance.policies.index')
    ->middleware('permission:airline_ticketing_new.insurance.view');

Route::get('ancillary/services', [AncillaryServiceController::class, 'index'])
    ->name('ancillary.services.index')
    ->middleware('permission:airline_ticketing_new.ancillary.view');

Route::get('bundles', [ServiceBundleController::class, 'index'])
    ->name('bundles.index')
    ->middleware('permission:airline_ticketing_new.bundles.view');
