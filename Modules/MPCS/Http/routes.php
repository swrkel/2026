<?php

use Modules\MPCS\Http\Controllers\F25FormController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

/*
|------------------------------------------------------------------------------
| IS2105: apply tenancy middleware only on a TENANT domain.
|------------------------------------------------------------------------------
| This group applied InitializeTenancyByDomain, PreventAccessFromCentralDomains
| and ScopeSessions unconditionally. That is correct on a multi-tenant install,
| and fatal on a single-database one.
|
| ep157-mpcsmananaioc is single-database by design - CENTRAL_DOMAIN is set to
| its own host, and it has 2 businesses and 49 users with NO tenants and NO
| domains rows. On that install PreventAccessFromCentralDomains did exactly what
| its name says: refused every request, because the host IS the central domain.
| Every MPCS page returned 404, not only the one reported.
|
| The middleware is now chosen per request: on a central domain the tenancy
| layer is skipped, because there is no tenant to initialise and nothing to
| scope. On a tenant domain the behaviour is unchanged.
|
| Detection uses the same config the tenancy package itself uses, so the two can
| never disagree.
*/
$mpcsHost = strtolower((string) request()->getHost());
$mpcsCentralDomains = array_map('strtolower', (array) config('tenancy.central_domains', []));
$mpcsIsCentralDomain = in_array($mpcsHost, $mpcsCentralDomains, true);

$mpcsMiddleware = array_values(array_filter([
    'web',
    $mpcsIsCentralDomain ? null : InitializeTenancyByDomain::class,
    $mpcsIsCentralDomain ? null : PreventAccessFromCentralDomains::class,
    $mpcsIsCentralDomain ? null : ScopeSessions::class,
    'IsInstalled',
    'auth',
    'SetSessionData',
    'language',
    'timezone',
    // tenant.context aborts 404 when it cannot resolve a tenant, so it belongs
    // with the rest of the tenancy layer rather than applying everywhere.
    $mpcsIsCentralDomain ? null : 'tenant.context',
]));

Route::group([
  'middleware' => $mpcsMiddleware,
  'prefix' => 'mpcs',
  'namespace' => 'Modules\MPCS\Http\Controllers',
], function () {
  Route::get('/20Form', 'F20FormController@index');
  Route::get('/get-20-form-settings', 'F20FormController@get20FormSettings');
  Route::post('/store-20-form-setting', 'F20FormController@store20FormSettings');
  Route::get('/20formsettings', 'F20FormController@mpcs20FormSettings');
  Route::get('/edit-20-form-settings/{id}', 'F20FormController@edit20FormSetting');
  Route::post('/update-20-form-settings/{id}', 'F20FormController@mpcs20Update');
  Route::get('/get-form-20-data', 'F20FormController@getFrom20Data');
  Route::get('/test-connection', 'F20FormController@testConnection');
  Route::get('/get-form-20-datas', 'F20FormController@getForm20Data');
  Route::get('/fetch-form-number', 'F20FormController@fetchFormNumber');

  // F15: all page, data, settings and category endpoints must run inside
  // the same domain tenancy/session scope. Register each URL only once.
  Route::get('/F15-9ABC', 'MPCSController@F159ABC');
  Route::get('/F15', 'F15FormController@index');
  // F15 Daily Report - New. Named routes are used by every sidebar/theme so
  // the page remains visible even when the application switches layouts.
  Route::get('/F15-New', 'F15FormController@dailyReportIndex')
    ->name('mpcs.f15-daily-report.index');
  Route::get('/F15-New/data', 'F15DailyReportController@data')
    ->name('mpcs.f15-daily-report.data');
  // IS2029: standalone print page - renders the report without the theme.
  Route::get('/F15-New/print', 'F15DailyReportController@print')
    ->middleware('auth')->name('mpcs.f15.daily.print');
  Route::post('/F15-New/save', 'F15DailyReportController@save')
    ->name('mpcs.f15-daily-report.save');

  // Stable lowercase aliases for servers/proxies that normalise URL casing.
  Route::get('/f15-new', 'F15FormController@dailyReportIndex');
  Route::get('/f15-daily-report', 'F15FormController@dailyReportIndex');
  Route::get('/F15New', 'F15FormController@dailyReportIndex');
  Route::get('/F15-Daily-Report', 'F15FormController@dailyReportIndex');
  Route::get('/f15/header/get-form-f15-data', 'F15FormController@getFormF15Data');
  Route::get('/get-15-form-setting', 'F15FormController@get15FormSetting');
  Route::get('/15formsettings', 'F15FormController@mpcs15FormSettings');
  Route::get('/get-15-setting-data', 'F15FormController@get15SettingData');
  Route::post('/store-15-form-setting', 'F15FormController@store15FormSetting');
  Route::get('/edit-15-form-settings/{id}', 'F15FormController@edit15FormSetting');
  Route::get('/delete-15-form-settings/{id}', 'F15FormController@delete15FormSetting');
  Route::post('/update-15-form-settings/{id}', 'F15FormController@mpcs15Update');
  Route::get('/f15-categories', 'F15FormController@getF15Categories');
  Route::post('/f15-categories/save', 'F15FormController@saveF15Categories');
  Route::get('/f15-category-selections', 'F15FormController@getF15CategorySelections');
  Route::post('/forms-setting/form159abc', 'FormsSettingController@saveForm159ABCSetting');
  Route::get('/forms-setting/form159abc', 'FormsSettingController@getForm159ABCSetting');
});

// Central/master systems must not run tenant.context because there is no
// tenant to resolve. Keep the existing tenant-domain stack unchanged, while
// giving the master system the normal authenticated/session middleware so its
// business context is populated before MPCS controllers and AJAX endpoints run.
$mpcsGeneralMiddleware = $mpcsIsCentralDomain
    ? $mpcsMiddleware
    : ['web', 'tenant.context'];

Route::group(['middleware' => $mpcsGeneralMiddleware, 'prefix' => 'mpcs', 'namespace' => 'Modules\MPCS\Http\Controllers'], function () {

  Route::get('/get-last-verified-form-f22', 'F22FormController@getLastVerifiedF22Form');
  Route::get('/get-last-verified-form-f22-header', 'F22FormController@getLastVerifiedF22FormHeader');
  Route::get('/get-form-f22-list', 'F22FormController@getF22FormList');
  Route::get('/get-form-f22-list_gain_loss', 'F22FormController@getF22FormListGainLoss');
  Route::get('/check-user-existence', 'F22FormController@checkUserExistence');
  Route::get('/fetch-pumps', 'F22FormController@fetchPumps');
  Route::post('/get_link_account_state', 'F22FormController@getLinkaccountstate');
  Route::get('/get-form-f16-list', 'F16AFormController@getF16FormList');
  Route::get('/get-form-f22', 'F22FormController@getF22Form');
  Route::post('/save-form-f22', 'F22FormController@saveF22Form');
  Route::post('/edit-form-16', 'F16AFormController@updateF16Form');
  Route::post('/edit-form-16/{id}', 'F16AFormController@save');
  Route::get('/print-form-f22-by-id/{header_id}', 'F22FormController@printF22FormById');
  Route::put('/update-form-f22/{id}', 'F22FormController@update');
  Route::get('/edit-form-f22/{id}', 'F22FormController@edit');
  Route::get('/view-form-f22/{id}', 'F22FormController@view');
  Route::get('/view-form-16/{id}', 'F16AFormController@view');
  Route::get('/edit-form-16/{id}', 'F16AFormController@edit');
  Route::post('/print-form-f22', 'F22FormController@printF22Form');
  Route::post('/save_f22_stock_taking', 'F22FormController@store_stock_taking');
  Route::get('/print-form-f16', 'F16AFormController@print');
  Route::get('/F22_stock_taking', 'F22FormController@F22StockTaking');
  Route::get('/F25', [F25FormController::class, 'index'])->middleware('auth');
  Route::get('/F25/form-no', [F25FormController::class, 'getFormNo'])->middleware('auth');
  Route::post('/F25/settings', [F25FormController::class, 'storeSetting'])->middleware('auth');
  Route::post('/F25/delivery-locations', [F25FormController::class, 'storeDeliveryLocation'])->middleware('auth');
  Route::post('/F25/delivery-locations/{id}', [F25FormController::class, 'updateDeliveryLocation'])->middleware('auth');
  Route::get('/F25/product-price', [F25FormController::class, 'getProductPrice'])->middleware('auth');
  Route::get('/F25/products', [F25FormController::class, 'getProducts'])->middleware('auth');
  Route::post('/F25/store', [F25FormController::class, 'store'])->middleware('auth');
  Route::get('/F25/list', [F25FormController::class, 'list'])->middleware('auth');
  Route::get('/F25/{id}/view', [F25FormController::class, 'show'])->middleware('auth');
  Route::get('/F25/{id}/preview', [F25FormController::class, 'preview'])->middleware('auth');
  Route::get('/F25/{id}/print', [F25FormController::class, 'print'])->middleware('auth');
  Route::get('/F25/{id}/pdf', [F25FormController::class, 'pdf'])->middleware('auth');
  Route::get('/F25/{id}/email', [F25FormController::class, 'email'])->middleware('auth');

  Route::get('/F10', 'F10FormController@index')->middleware('auth');
  Route::get('/get-form-f10-list', 'F10FormController@getF10FormList')->middleware('auth');
  Route::get('/get-f10-form-details/{id}', 'F10FormController@getF10FormDetails')->middleware('auth');
  // MA-002 (IS-1915 #1): the F10 form has always called this on load; it was
  // the only F10 endpoint with no route, so the page reported
  // "The route mpcs/F10/get-current-f10-number could not be found".
  Route::get('/F10/get-current-f10-number', 'F10FormController@getCurrentF10Number')->middleware('auth');
  // MA-002 (IS-1915 #2): the printable receipt.
  Route::get('/F10/receipt/{id}/print', 'F10FormController@printReceipt')->middleware('auth');
  Route::post('/F10/opening-numbers', 'F10FormController@storeOpeningNumbers')->middleware('auth');
  Route::post('/F10/managers', 'F10FormController@storeManager')->middleware('auth');
  Route::post('/F10/managers/toggle/{id}', 'F10FormController@toggleManagerStatus')->middleware('auth');
  Route::post('/F10/save-receipt', 'F10FormController@storeReceipt')->middleware('auth');

    // Signature routes
    Route::get('/F10/signatures/list', 'SignatureController@list')->middleware('auth');
    Route::post('/F10/signatures/upload', 'SignatureController@upload')->middleware('auth');
    Route::delete('/F10/signatures/delete/{id}', 'SignatureController@delete')->middleware('auth');
    Route::get('/F10/signatures/users', 'SignatureController@getUsers')->middleware('auth');
    Route::get('/F10/signatures/designations', 'SignatureController@getDesignations')->middleware('auth');
    Route::get('/F10/signatures/user-designation', 'SignatureController@getUserDesignation')->middleware('auth');
    Route::post('/F10/signatures/add-user', 'SignatureController@addUser')->middleware('auth');
    Route::post('/F10/signatures/add-designation', 'SignatureController@addDesignation')->middleware('auth');

  Route::get('/form-set-1', 'MPCSController@FromSet1')->middleware('auth');
  Route::get('/form-9a', 'MPCSController@From9A')->middleware('auth');
  Route::get('/form-9c', 'MPCSController@From9C')->middleware('auth');
  Route::get('/get-receipts-data', 'MPCSController@get9AFormData');//->middleware('auth');
  Route::get('/form-9ccr', 'MPCSController@From9CCR');//->middleware('auth');
  Route::get('/mpcs/F14B', 'F14FormController@index');

  Route::get('/form9a-settings/create', 'Form9ASettingsController@create')->name('form9a-settings.create');
  Route::post('/form9a-settings/store', 'Form9ASettingsController@store')->name('form9a-settings.store');
  Route::get('/form9a-settings/edit/{id}', 'Form9ASettingsController@edit')->name('form9a-settings.edit');
  Route::post('/form9a-settings/update/{id}', 'Form9ASettingsController@update')->name('form9a-settings.update');
  Route::get('/form9c-settings/create', 'Form9CSettingsController@create')->name('form9c-settings.create')->middleware('auth');
  Route::post('/form9c-settings/store', 'Form9CSettingsController@store')->name('form9c-settings.store')->middleware('auth');
  Route::post('/form9c-settings_update/{id}', 'Form9CSettingsController@update')->name('form9c-settings.update')->middleware('auth');
  Route::get('/edit-form-9c-settings/{id}', 'Form9CSettingsController@edit')->name('form9c-settings.edit')->middleware('auth');
  Route::get('/get-form-9c-settings', 'Form9CSettingsController@index')->middleware('auth');

  Route::get('/get-form-9ccr-settings', 'Form9CCRSettingsController@index');
  Route::get('/form9ccr-settings/create', 'Form9CCRSettingsController@create');
  Route::post('/form9ccr-settings/store', 'Form9CCRSettingsController@store');
  Route::post('/form9ccr-settings_update/{id}', 'Form9CCRSettingsController@update');
  Route::get('/edit-form-9ccr-settings/{id}', 'Form9CCRSettingsController@edit');

  Route::get('/get-form-9a-settings', 'Form9ASettingsController@index');
  Route::get('/get-9a-form', 'Form9ASettingsController@get9AForm');
  Route::get('/get-form-16a', 'MPCSController@get16AForm');
  Route::get('/get_previous_value_16a', 'F16AFormController@getPreviousValue16a');
  Route::get('/F14', 'F14FormController@index')->middleware('auth');
  Route::get('/get_previous_value_9c', 'MPCSController@getPreviousValue9CForm');
  Route::get('/get-9c-form', 'MPCSController@get9CForm');
  Route::get('/get-9a-form_value', 'MPCSController@get9AForm');
  Route::get('/get-9ccash-form', 'MPCSController@get9CCashForm');
  Route::get('/get-9ccredit-form', 'MPCSController@get9CCreditForm');
  Route::get('/get-9b-form-data', 'MPCSController@get9BFormData');
  Route::get('/get-payments-data', 'MPCSController@getPaymentsData');
  Route::get('/fetch-f16a-form-number', 'F16AFormController@fetchFormNumber');
  Route::get('/get-f16a-products', 'F16AFormController@getF16AProducts');
  Route::get('/F21', 'F21CFormController@index');
  Route::get('/form9ccash', 'Form9CCashController@index');
  Route::post('/popup-form', 'Form9CCashController@store');
  // Add to your routes file
  Route::get('/check-approval-status', 'F22FormController@checkApprovalStatus');
  Route::get('/approve', 'F22FormController@approveForm');
  //By Zamaluddin : Time 04:20 PM : 28 January 2025 
  Route::post('/get-text-store', 'Form9ASettingsController@TextDetailstore');
  Route::get('/get-text-get', 'Form9ASettingsController@TextDetailget');
  Route::get('/get-text-edit', 'Form9ASettingsController@TextDetailedit');
  Route::delete('/delete-text-detail', 'Form9ASettingsController@delete');




  Route::get('/get_21_c_form_all_query', 'F21CFormController@get_21_c_form_all_query');
  Route::get('/get-21c-form', 'MPCSController@get21CForm');
  Route::get('/get-9c-forms', 'MPCSController@get21CForms');
  //Route::get('/get_opening_stock_21_form', 'MPCSController@getOpeningStock21Form');
  Route::post('/save-form-f16', 'F16AFormController@saveF16Form');
  Route::post('/save-all-form-f16', 'F16AFormController@saveAllF16Forms');
  Route::get('/get-form-14b', 'F20F14bFormController@getFrom14B');
  Route::get('/get-form-20', 'F20F14bFormController@getFrom20');
  Route::resource('/F14B_F20_Forms', 'F20F14bFormController')->names('mpcs.F14B_F20_Forms');

  Route::get('/F14B', 'NewF14FormController@index');
  Route::get('/get-form-14', 'NewF14FormController@getForm14');

  Route::get('/list-F17', 'F17FormController@list');
  Route::get('/F17/get-sub-categories', 'F17FormController@getSubCategories');
  Route::get('/F17/get-brands', 'F17FormController@getBrands');
  Route::get('/F17/get-products', 'F17FormController@getProducts');
  Route::get('/F17/get-product-brand', 'F17FormController@getProductBrand');
  Route::get('/F17/get-units', 'F17FormController@getProductUnits');
  Route::resource('/F17', 'F17FormController')->names('mpcs.F17');

  // F 18 – Prefix & Numbers configuration
  Route::get('/F18', 'F18FormController@index');
  Route::post('/F18/prefix-numbers', 'F18FormController@storePrefixNumbers')->name('F18.prefix_numbers.store');
  Route::get('/F18/products', 'F18FormController@getProducts')->name('F18.products');
  Route::get('/F18/form-no', 'F18FormController@getFormNo')->name('F18.form_no');
  Route::get('/F18/product-prices', 'F18FormController@getProductPrices')->name('F18.product_prices');
  Route::post('/F18', 'F18FormController@store')->name('F18.store');
  Route::get('/F18/list-data', 'F18FormController@listData')->name('F18.list_data');
  Route::get('/F18/{id}/view', 'F18FormController@show')->name('F18.show');
  Route::get('/F18/{id}/print', 'F18FormController@printForm')->name('F18.print');

  Route::get('/form-opening-value/print/{id}', 'FormOpeningValueController@print');
  Route::resource('/form-opening-value', 'FormOpeningValueController')->names('mpcs.form-opening-value');
  Route::post('approve', 'F22FormController@approveF22');

  // F22 Authorized Signatures CRUD
  // AJAX endpoints for signature management
  Route::get('/get-f22-signatures', 'F22FormController@getF22Signatures');
  Route::post('/store-f22-signature', 'F22FormController@storeF22Signature');
  Route::get('/f22-signatures/{id}/view', 'F22FormController@viewF22Signature');
  Route::get('/f22-signatures/{id}/edit', 'F22FormController@editF22Signature');
  Route::delete('/f22-signatures/{id}', 'F22FormController@destroyF22Signature');

  Route::post('/forms-setting/formf33', 'FormsSettingController@postFormF22Setting');
  Route::get('/forms-setting/formf33', 'FormsSettingController@getFormF22Setting');
  Route::get('/forms-setting/form21C/modal', 'FormsSettingController@getFormF21Setting');
  Route::post('/forms-setting/form21C/modal', 'FormsSettingController@postFormF21Setting');
  Route::post('/forms-setting/form21C', 'FormsSettingController@postForm21CSetting');
  Route::get('/forms-setting/form21C', 'FormsSettingController@getForm21CSetting');
  Route::post('/forms-setting/form16a', 'FormsSettingController@postForm16ASetting');
  Route::get('/forms-setting/form16a', 'FormsSettingController@getForm16ASetting');
  Route::post('/forms-setting/form9c', 'FormsSettingController@postForm9CSetting');
  Route::get('/forms-setting/form9c', 'FormsSettingController@getForm9CSetting');
  Route::get('/forms-setting/form14c', 'FormsSettingController@getForm14CSetting');
  Route::post('/forms-setting/form14c', 'FormsSettingController@updateF14FormSetting');
  Route::get('/forms-setting/form17c', 'FormsSettingController@getForm17CSetting');
  Route::get('/forms-setting/form20c', 'FormsSettingController@getForm20CSetting');

  Route::resource('/forms-setting', 'FormsSettingController')->names('mpcs.forms-setting')->middleware('auth');
  Route::get('/16A', 'F16AFormController@index');



  Route::get('/get-16a-form-setting', 'FormsSettingController@get16AFormSetting');
  Route::get('/16aformsettings', 'FormsSettingController@mpcs16aFormSettings');
  Route::post('/store-16a-form-setting', 'FormsSettingController@store16aFormSetting');
  Route::get('/edit-16-a-form-settings/{id}', 'FormsSettingController@edit16aFormSetting');
  Route::post('/update-16a-form-settings/{id}', 'FormsSettingController@mpcs16Update');

  Route::get('/21CForm', 'F21FormController@index');
  Route::get('/get-21c-form-setting', 'F21FormController@get21CFormSettings');
  Route::get('/get-subcategory-pump/{id}', 'F21FormController@getSubcategoryPumps');

  Route::post('/add-newpump-row', 'F21FormController@addNewPumpRow');
  Route::post('/store-21c-form-setting', 'F21FormController@store21cFormSettings');
  Route::get('/21cformsettings', 'F21FormController@mpcs21cFormSettings');
  Route::get('/edit-21-c-form-settings/{id}', 'F21FormController@edit21cFormSetting');
  Route::post('/update-21c-form-settings/{id}', 'F21FormController@mpcs21Update');


  Route::get('/21Form', 'F21FormController@get21Form');
  Route::get('/getpos', 'F21FormController@getPos');
  Route::get('/get-purchase-order', 'F21FormController@getPurchaseOrder');
  Route::get('/get-sales-return', 'F21FormController@getSellReturn');
  Route::get('/get-purchase-return', 'F21FormController@getPurchaseReturn');
  Route::get('/get-settlement', 'F21FormController@getSettlement');
  Route::get('/get-all-f21-transactions', 'F21FormController@getAllTransactions');
  Route::get('/get-f21-form-number', 'F21FormController@getF21FormNumber');

  Route::get('/get-products-by-category', 'MPCSController@getProductsByCategory');
  Route::get('/get-sub-categories', 'F21FormController@getSubCategories');

  // F20 Form - CDS (new clean software page based on the original F20 CDS paper form)
  Route::get('/F20-CDS', 'F20CDSFormController@index');
  Route::post('/F20-CDS', 'F20CDSFormController@store');
  Route::post('/F20-CDS/settings', 'F20CDSFormController@storeSettings');
  Route::get('/F20-CDS/list', 'F20CDSFormController@list');
  Route::get('/F20-CDS/{id}/print', 'F20CDSFormController@print');
  Route::get('/get-products-by-subcategory/{id}', 'F22FormController@getBySubCategory');
});
