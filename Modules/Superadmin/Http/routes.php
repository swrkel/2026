<?php

use Modules\Superadmin\Http\Controllers\BusinessController;

Route::group(['prefix' => 'superadmin', 'namespace' => 'Modules\Superadmin\Http\Controllers'], function () {
    Route::get('/notify-expired', 'BusinessController@notifyExpired');
    Route::get('/update-permissions', 'BusinessController@updatePerms');
});

// 8051: runtime payload is read-only and must remain available on authenticated
// tenant screens even when the DayEnd middleware would block a normal page.
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'tenant.context'], 'prefix' => 'superadmin', 'namespace' => 'Modules\Superadmin\Http\Controllers'], function () {
    Route::get('/banner-idle/runtime.js', 'BannerManagementController@idleRuntime')
        ->name('superadmin.banner.idle.runtime');
    Route::get('/banner-idle/payload', 'BannerManagementController@idlePayload')
        ->name('superadmin.banner.idle.payload');
});

Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', 'tenant.context'], 'prefix' => 'superadmin', 'namespace' => 'Modules\Superadmin\Http\Controllers'], function () {
    
    Route::get('default-business-types', 'DefaultBusinessTypeController@index');
    Route::get('default-business-types/create', 'DefaultBusinessTypeController@create');
    Route::post('default-business-types', 'DefaultBusinessTypeController@store');
    Route::get('default-business-types/{id}/edit', 'DefaultBusinessTypeController@edit');
    Route::put('default-business-types/{id}', 'DefaultBusinessTypeController@update');
    Route::delete('default-business-types/{id}', 'DefaultBusinessTypeController@destroy');
    Route::get('all-business-types', 'DefaultBusinessTypeController@allBusinessTypes');

    Route::get('locations', 'LocationsController@index');
    Route::get('add-country', 'LocationsController@addCountry');
    Route::post('add-country', 'LocationsController@addCountry');
    Route::get('countries', 'LocationsController@countries');
    Route::get('provinces', 'LocationsController@provinces');
    Route::get('add-province', 'LocationsController@addProvince');
    Route::post('add-province', 'LocationsController@addProvince');
    Route::get('districts', 'LocationsController@districts');
    Route::get('add-district', 'LocationsController@addDistrict');
    Route::post('add-district', 'LocationsController@addDistrict');
    Route::get('areas', 'LocationsController@areas');
    Route::get('get-provinces/{country_id}', 'LocationsController@getProvinces');
    Route::get('get-districts/{province_id}', 'LocationsController@getDistricts');
    Route::get('get-areas/{district_id}', 'LocationsController@getAreas');
    Route::get('areas', 'LocationsController@areas');
    Route::get('add-areas', 'LocationsController@addarea');
    Route::post('add-areas', 'LocationsController@addarea');

    Route::resource('default-notification-templates', 'DefaultNotificationTemplateController')->only(['index', 'store']);
    Route::resource('tax-rate', 'DefaultTaxRateController');
    Route::resource('group-tax', 'DefaultGroupTaxController');

    Route::resource('sms-reminder-settings', 'SmsReminderSettingController');


    Route::resource('sms-api-clients', 'SmsApiClientController');
    Route::resource('map-business-tanks', 'MapBusinessTankController');

    Route::resource('smsrefill-package', 'SmsRefillPackageController');

    Route::get('sms-summary', 'RefillBusinessController@businessSMSSummary');
    Route::get('get-history', 'RefillBusinessController@smsHistory');
    Route::resource('refill-business', 'RefillBusinessController');

    Route::get('/install', 'InstallController@index');
    Route::get('/install/update', 'InstallController@update');

    Route::get('/', 'SuperadminController@index');
    Route::get('/stats', 'SuperadminController@stats');

    Route::get('/{business_id}/toggle-active/{is_active}', 'BusinessController@toggleActive');
    Route::get('/business/back-to-superadmin', 'BusinessController@backToSuperadmin');
    Route::get('/business/login-as-business/{id}', 'BusinessController@loginAsBusiness');
    Route::get('/business/get-locations-for-businesses', 'BusinessController@getBusinessLocations');
    Route::get('/business-location-overview', 'BusinessLocationOverviewController@index')
        ->name('superadmin.business-locations-overview');

    // 8051: standalone Super Admin page. It is deliberately not part of
    // All Businesses > Manage New because this is one central cross-tenant setting.
    // 8055: Master Super Admin is a code-credential protected central page.
    Route::get('/master-super-admin', 'MasterSuperAdminController@index')->name('superadmin.master-super-admin.index');
    Route::post('/master-super-admin/authenticate', 'MasterSuperAdminController@authenticate')->name('superadmin.master-super-admin.authenticate');
    Route::put('/master-super-admin', 'MasterSuperAdminController@update')->name('superadmin.master-super-admin.update');
    Route::post('/master-super-admin/logout', 'MasterSuperAdminController@logout')->name('superadmin.master-super-admin.logout');

    Route::get('/banner-management', 'BannerManagementController@index')
        ->name('superadmin.banner-management.index');
    Route::post('/banner-management', 'BannerManagementController@update')
        ->name('superadmin.banner-management.update');
    Route::get('/banner-management/business-options', 'BannerManagementController@businessOptions')
        ->name('superadmin.banner-management.business-options');
    Route::get('/business/get-manage-business-options', 'BusinessController@getManageBusinessOptions');
    Route::get('/business/manage/{id}', [BusinessController::class, 'manage']);
    Route::get('/business/{id}/manage-sidebar-modules', 'BusinessController@manageSidebarModules');

    // Per-module Manage page (MA 007 split, step 1 - read only).
    // Module index with search across modules AND their pages.
    Route::get('/business/{id}/manage/modules', 'BusinessController@manageModuleIndex');
    Route::get('/business/{id}/manage/module/{moduleKey}', 'BusinessController@manageModule');
    Route::post('/business/{id}/manage/module/{moduleKey}', 'BusinessController@saveModulePermissions');
    Route::post('/business/{id}/save-sidebar-modules', 'BusinessController@saveSidebarModules');
    Route::post('/business/save-manage/{id}', 'BusinessController@saveManage');
    Route::post('/business/save-manage-permissions-fast/{id}', 'BusinessController@saveManagePermissionsFast');
    Route::post('/business/{businessId}/images/update', 'BusinessController@updateImages')->name('business.images.update');
    Route::delete('/business/{businessId}/images/delete/{type}', 'BusinessController@deleteImage')->name('business.images.delete');
    /*
     * S679-1: add a user to a specific business from All Business -> view.
     * Declared BEFORE the business resource so /business/{id}/users/create is
     * not swallowed by the resource's own {business} parameter.
     */
    Route::get('/business/{business_id}/users/create', 'BusinessUserController@create')
        ->name('superadmin.business.users.create');
    Route::post('/business/{business_id}/users', 'BusinessUserController@store')
        ->name('superadmin.business.users.store');

    Route::resource('/business', 'BusinessController');
    Route::get('/business/{id}/destroy', 'BusinessController@destroy');
    Route::post('/account-entry/massdestroy', 'EditAccountEntriesController@massDestroy');
    Route::any('/business', ['as' => 'filter.business', 'uses' => 'BusinessController@index']);
    Route::any('/business/admin_register', ['as' => 'admin.business_register', 'uses' => 'BusinessController@store']);
    Route::any('/business/hospital_register', ['as' => 'hospital_register', 'uses' => 'BusinessController@hospital_register']);
    Route::any('/business/pharmacy_register', ['as' => 'pharmacy_register', 'uses' => 'BusinessController@pharmacy_register']);
    Route::any('/business/laboratory_register', ['as' => 'laboratory_register', 'uses' => 'BusinessController@laboratory_register']);

    Route::get('/packages/get_option_variables', 'PackagesController@getOptionVariables'); //for normal pacakge
    Route::resource('/packages', 'PackagesController');

    Route::resource('/account-numbers', 'AccountNumbersController');

    Route::get('/packages/{id}/destroy', 'PackagesController@destroy');

    Route::get('/settings', 'SuperadminSettingsController@edit')->name('settings');
    Route::get('/settings/product-categories', 'SuperadminSettingsController@getProductCategories');
    Route::get('/settings/add-product-category', 'SuperadminSettingsController@addProductCategory');
    Route::post('/settings/store-product-category', 'SuperadminSettingsController@storeProductCategory');
    Route::get('/settings/edit-product-category/{id}', 'SuperadminSettingsController@editProductCategory');
    Route::put('/settings/update-product-category/{id}', 'SuperadminSettingsController@updateProductCategory');
    Route::delete('/settings/destroy-product-category/{id}', 'SuperadminSettingsController@destroyProductCategory');
    Route::get('/settings/expense-categories', 'SuperadminSettingsController@getExpenseCategories');
    Route::get('/settings/add-expense-category', 'SuperadminSettingsController@addExpenseCategory');
    Route::post('/settings/store-expense-category', 'SuperadminSettingsController@storeExpenseCategory');
    Route::get('/settings/edit-expense-category/{id}', 'SuperadminSettingsController@editExpenseCategory');
    Route::put('/settings/update-expense-category/{id}', 'SuperadminSettingsController@updateExpenseCategory');
    Route::delete('/settings/destroy-expense-category/{id}', 'SuperadminSettingsController@destroyExpenseCategory');
    Route::get('/settings/search-tenants', 'SuperadminSettingsController@searchTenants');
    // landing pages
    Route::get('/pages', 'SuperadminSettingsController@pages')->name('pages');

    Route::get('/landing-languages', 'SuperadminSettingsController@landing_languages')->name('landing-languages');

    Route::get('/landing-settings', 'SuperadminSettingsController@landAdminSettings')->name('landing-settings');
    Route::post('/landing-settings', 'SuperadminSettingsController@changeSettings')->name('landing-settings.store');

    Route::get('/edit-page/{id}', 'SuperadminSettingsController@editPage')->name('edit-page');
    Route::post('/save-page/{id}', 'SuperadminSettingsController@savePage')->name('save-page');

    Route::get('/landing-pages', 'SuperadminSettingsController@landingSettings')->name('landing-pages');
    Route::post('/landing-pages', 'SuperadminSettingsController@savelandingSettings')->name('landing-pages.store');


    Route::put('/settings', 'SuperadminSettingsController@update');

    Route::post('add-ad', 'SuperadminSettingsController@saveAd')->name('add.ad');
    Route::get('edit-ad/{id}', 'SuperadminSettingsController@editAd')->name('edit.ad');
    Route::post('update-ad', 'SuperadminSettingsController@updateAd')->name('update.ad');
    Route::get('delete-ad', 'SuperadminSettingsController@deleteAd')->name('delete.ad');
    Route::post('/get-ad-slots-data', 'SuperadminSettingsController@getAdPageSlot')->name('ad.get-ad-slots-data');

    #ad slot
    Route::post('add-ad-slot', 'SuperadminSettingsController@saveAdSlot')->name('add.adslot');

    Route::post(
    'banner/pause-settings',
    'SuperadminSettingsController@savePauseSettings'
    )->name('banner.pause.settings');
    Route::post('add-banner', 'SuperadminSettingsController@saveBanner')->name('add.banner');

    Route::get('edit-banner/{id}', 'SuperadminSettingsController@editBanner')->name('edit.banner');
    
    Route::post('update-banner', 'SuperadminSettingsController@updateBanner')->name('update.banner');
    
    Route::post('delete-banner', 'SuperadminSettingsController@deleteBanner')->name('delete.banner');
    
    /* Optional: Tenant Auto Search (if needed later) */
    Route::post('get-tenants-data', 'SuperadminSettingsController@getTenants')->name('banner.get-tenants');
    Route::get('/edit-subscription/{id}', 'SuperadminSubscriptionsController@editSubscription');
    Route::post('/update-subscription', 'SuperadminSubscriptionsController@updateSubscription');
    Route::resource('/superadmin-subscription', 'SuperadminSubscriptionsController');
    Route::get('/get-option-variables/{id}/{business_id}', 'CompanyPackageVariableController@getOptionVariables'); //only for comapny pacakge eg manage
    Route::resource('/company-package-variables', 'CompanyPackageVariableController');
    Route::resource('/package-variables', 'PackageVariableController');

    Route::get('/communicator', 'CommunicatorController@index');
    Route::post('/communicator/send', 'CommunicatorController@send');
    Route::get('/communicator/get-history', 'CommunicatorController@getHistory');

    Route::resource('/frontend-pages', 'PageController');
    Route::resource('/tenant-management', 'TenantManagementController');
    Route::resource('/help-explanation', 'HelpExplanationController');
    Route::get('/default-manage-users/get-business-data', 'DefaultManageUserController@getBusinessData');
    Route::resource('/default-manage-users', 'DefaultManageUserController');
    Route::resource('/default-role', 'DefaultRoleController');
    Route::get('/tank-dip-chart/import', 'TankDipChartController@getImport');
    Route::post('/tank-dip-chart/import', 'TankDipChartController@postImport');
    Route::get('/tank-dip-chart-details/get-reading-value/{id}', 'TankDipChartController@getDipReadingValue');
    Route::get('/tank-dip-chart/get-by-id/{id}', 'TankDipChartController@getTankDipById');
    Route::resource('/tank-dip-chart', 'TankDipChartController');
    Route::resource('/referrals', 'ReferralController');
    Route::resource('/referral-starting-code', 'ReferralStartingCodeController');
    Route::resource('/give-away-gifts', 'GiveAwayGiftsController');

    Route::any('family-subscription/pay', 'FamilySubscriptionController@pay');
    Route::post('family-subscription/confirm', 'FamilySubscriptionController@confirm');
    Route::get('family-subscription/get-option-variable', 'FamilySubscriptionController@getOptionVariables');
    Route::get('/family-subscription/patient', 'FamilySubscriptionController@getPatientSubscriptions');
    Route::resource('/family-subscription', 'FamilySubscriptionController');

    Route::get('/get-family-subscription', 'FamilySubscriptionController@getFamilyPackages');

    Route::post('/import-file', 'ImportExportController@importFile');
    Route::get('/export-file', 'ImportExportController@exportFile');
    Route::resource('/imports-exports', 'ImportExportController');
    Route::get('/edit-account-transaction/{transaction_id}/{business_id}', 'EditAccountEntriesController@editAccountTransaction');
    Route::get('/get-account-drop-down-by-business/{buisness_id}', 'EditAccountEntriesController@getAccountDropdownByBusiness');
    Route::get('/list-edit-account-entries', 'EditAccountEntriesController@listEditAccountTransaction');
    Route::resource('/edit-account-entries', 'EditAccountEntriesController');
    Route::get('/get-cheque-details', 'EditAccountEntriesController@getChequeDetails');
    Route::get('/edit-contact-transaction/get-ledger', 'EditContactEntriesController@getLedger');
    Route::get('/edit-contact-transaction/{transaction_id}/{business_id}', 'EditContactEntriesController@editContactTransaction');
    Route::get('/get-contact-drop-down-by-business/{buisness_id}/{type}', 'EditContactEntriesController@getContactDropdownByBusiness');
    Route::get('/list-edit-contact-entries', 'EditContactEntriesController@listEditContactTransaction');
    Route::resource('/edit-contact-entries', 'EditContactEntriesController');
    Route::get('/agents/get-districts/{country_id}', 'AgentController@getDistrictsByCountry');
    Route::get('/agents/get-cities/{district_id}', 'AgentController@getCitiesByDistrict');
    Route::post('/agents/store-district', 'AgentController@storeDistrict');
    Route::post('/agents/store-city', 'AgentController@storeCity');
    Route::get('/agents/next-referral-code', 'AgentController@getNextReferralCode');
    Route::resource('/agents', 'AgentController')->names('superadmin.agents');
    Route::resource('/referral-group', 'ReferralGroupController');
    Route::resource('/income-method', 'IncomeMethodController');
    Route::get('/petro-quota-setting', '\Modules\Petro\Http\Controllers\VehicleController@petro_qouta_setting');
    Route::post('/petro-quota-setting', '\Modules\Petro\Http\Controllers\VehicleController@petro_qouta_setting');
    Route::delete('/petro-quota-setting/{type}/{id}', '\Modules\Petro\Http\Controllers\VehicleController@petro_qouta_setting_destory');

    Route::get('/petro-qouta-setting-ajax', '\Modules\Petro\Http\Controllers\VehicleController@petro_qouta_setting_ajax');
    Route::post('/petro-qouta-setting-ajax', '\Modules\Petro\Http\Controllers\VehicleController@petro_qouta_setting_ajax');


    Route::get('/user-locations', '\Modules\Superadmin\Http\Controllers\UserLocationsController@index')->name('userlocations.index');



});
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', 'tenant.context'], 'namespace' => 'Modules\Superadmin\Http\Controllers'], function () {
    Route::post('/family-subscription/notify-payhere', 'FamilySubscriptionController@notifyPayhere');
    Route::post('/pay-online/payhere-notify', 'PayOnlineController@notifyPayhere');
    Route::post('/subscription/payhere/confirm', 'SubscriptionController@payhereNotify')->name('subscription-payhere-confirm');
    Route::get('/subscription/{package_id}/get_package_variables', 'SubscriptionController@getPackageVariables');
});
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', 'tenant.context'], 'namespace' => 'Modules\Superadmin\Http\Controllers'], function () {
    //Routes related to paypal checkout
    Route::get(
        '/subscription/{package_id}/paypal-express-checkout',
        'SubscriptionController@paypalExpressCheckout'
    );

    Route::post('/pay-online/initiate-payhere', 'PayOnlineController@initiatePayhere');
    Route::resource('/pay-online', 'PayOnlineController');

    //Routes related to pesapal checkout
    Route::get('/subscription/{package_id}/pesapal-callback', ['as' => 'pesapalCallback', 'uses' => 'SubscriptionController@pesapalCallback']);


    Route::any('/subscription/{package_id}/pay', 'SubscriptionController@pay');
    Route::any('/subscription/{package_id}/{gateway}/check-status', 'SubscriptionController@checkStatus');
    Route::any('/subscription/{package_id}/confirm', 'SubscriptionController@confirm')->name('subscription-confirm');
    Route::post('/subscription/payhere/payhereInitailData', 'SubscriptionController@payhereInitailData')->name('subscription-payhere-initaildata');
    Route::get('/all-subscriptions', 'SubscriptionController@allSubscriptions');

    Route::get('/subscription/{package_id}/register-pay', 'SubscriptionController@registerPay')->name('register-pay');

    Route::resource('/subscription', 'SubscriptionController');
});
Route::middleware('tenant.context')->group(function () {
    Route::get('/pricing', 'Modules\Superadmin\Http\Controllers\PricingController@index')->name('pricing')->middleware('web');
    Route::get('/page/{slug}', 'Modules\Superadmin\Http\Controllers\PageController@showPage')->name('frontend-pages');
});
