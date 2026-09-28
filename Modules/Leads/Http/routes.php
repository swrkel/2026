<?php

Route::group(['middleware' => ['web','tenant.context'], 'prefix' => 'leads', 'namespace' => 'Modules\Leads\Http\Controllers'], function()
{
    Route::post('/leads/toggle-valid/{id}', 'LeadsController@toggleStatus');
    Route::post('/leads/bulk-valid', 'LeadsController@massValid');
    Route::post('/leads/bulk-invalid', 'LeadsController@massInvalid');
    Route::get('/leads/add-client-response/{id}', 'LeadsController@addClientResponse');
    Route::post('/leads/add-client-response', 'LeadsController@clientResponse');

    // 8001 / LEADS-02: first Leads menu page.
    // Uses the same controller, validation and store flow as Leads > Leads > Add,
    // but exposes it as a normal first page inside the Leads module.
    Route::get('/add-leads', 'LeadsController@create')->name('leads.add-leads');
    Route::post('/add-leads', 'LeadsController@store')->name('leads.add-leads.store');

    Route::resource('/leads', 'LeadsController');
    Route::resource('/import', 'ImportLeadsController');
    Route::resource('/day-count', 'DayCountController');
    Route::resource('/district', 'DistrictController')->names('leads.district');
    Route::resource('/town', 'TownController');
    Route::resource('/settings', 'SettingController')->names('leads.settings');
    Route::resource('/category', 'CategoryController');
    Route::resource('/labels', 'LabelController');

    Route::post('/ajax_mobile', 'AjaxController@ajax_mobile')->name('leads.ajax_mobile');
    Route::post('/ajax_town', 'AjaxController@ajax_town')->name('leads.ajax_town');
    Route::post('/ajax_district', 'AjaxController@ajax_district')->name('leads.ajax_district');
});
