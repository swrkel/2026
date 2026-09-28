<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PG017 - Petro General Dip Management small action routes
|--------------------------------------------------------------------------
| These routes keep the existing DipManagementController business logic intact
| through small wrapper controllers. This reduces future file size/risk without
| changing working dip calculations, tenant filtering, or views.
*/

/*
| S665 follow-up: /dip-management-general now serves the WORKING dip page.
|
| There were two Dip Management pages in this module:
|
|   dip_management/index.blade.php      - the complete one. Dip Report, Dip
|                                         Resetting, Tank Dip Chart, the Add New
|                                         Dip modal, and all the JavaScript that
|                                         drives them.
|
|   dip_management_pg/index.blade.php   - a newer shell. Its four tabs were
|                                         four-line placeholders reading "This
|                                         tab is separated for Petro General
|                                         maintenance" - no Add button, no table,
|                                         no data.
|
| This URL pointed at the SHELL, which is why the Readings tab had no Add
| button: the tab had nothing in it at all. The same was true of Charts,
| Resettings and Reports.
|
| Rather than rebuild four tabs that already exist and work, the route now
| renders the complete page. One page for this purpose, at this URL, as
| requested.
|
| DipManagementController@index supplies business_locations, tanks, products and
| message - everything that page and its modals need.
|
| The dip_management_pg views and DipIndexController are left in place, unused,
| rather than deleted: they are somebody's partial rebuild and are not mine to
| remove. Nothing routes to them now.
*/
Route::get('/dip-management-general', 'DipManagementController@index')
    ->name('petrogeneral.dip_management.index');

Route::get('/get-dip-chart', 'Dip\\DipChartListController@index')
    ->name('petrogeneral.dip_management.dip_chart.list');
Route::get('/add-dip-chart', 'Dip\\DipChartCreateController@create')
    ->name('petrogeneral.dip_management.dip_chart.create');
Route::post('/save-dip-chart', 'Dip\\DipChartStoreController@store')
    ->name('petrogeneral.dip_management.dip_chart.store');
Route::get('/edit-dip-chart/{id}', 'Dip\\DipChartEditController@edit')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.dip_management.dip_chart.edit');
Route::post('/update-dip-chart/{id}', 'Dip\\DipChartUpdateController@update')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.dip_management.dip_chart.update');
Route::delete('/delete-dip-chart/{id}', 'Dip\\DipChartDeleteController@destroy')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.dip_management.dip_chart.destroy');

Route::get('/add-dip-chart-reading/{id}', 'Dip\\DipChartReadingCreateController@create')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.dip_management.dip_chart_reading.create');
Route::post('/add-dip-chart-reading/{id}', 'Dip\\DipChartReadingStoreController@store')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.dip_management.dip_chart_reading.store');

Route::get('/get-dip-report', 'Dip\\DipReportController@index')
    ->name('petrogeneral.dip_management.report');

Route::get('/add-new-dip', 'Dip\\NewDipCreateController@create')
    ->name('petrogeneral.dip_management.new_dip.create');
Route::post('/save-new-dip-reading', 'Dip\\NewDipStoreController@store')
    ->name('petrogeneral.dip_management.new_dip.store');

Route::get('/get-dip-resetting', 'Dip\\DipResettingListController@index')
    ->name('petrogeneral.dip_management.resetting.list');
// All tanks at a location, for the multi-tank Dip Reset form.
Route::get('/dip-reset/tanks-by-location/{location_id}', 'DipManagementController@getTanksByLocation')
    ->name('petrogeneral.dip_management.resetting.tanks_by_location');
Route::get('/add-resetting-dip', 'Dip\\DipResettingCreateController@create')
    ->name('petrogeneral.dip_management.resetting.create');
Route::post('/save-resetting-dip', 'Dip\\DipResettingStoreController@store')
    ->name('petrogeneral.dip_management.resetting.store');

Route::get('/get-tank-product/{tank_id}', 'Dip\\TankProductController@show')
    ->where('tank_id', '[0-9]+')
    ->name('petrogeneral.dip_management.tank_product');
/*
 |-----------------------------------------------------------------------------
 | Inventory Adjustment Account for the Dip Reset form.
 |-----------------------------------------------------------------------------
 |
 | The core endpoint, StockAdjustmentController@getInventoryAdjustmentAccount,
 | returns an EMPTY dropdown unless the business subscribes to 'access_account':
 |
 |     if ($account_access == 0) { return $result; }
 |
 | A superadmin bypasses subscription checks, which is why the list appeared for
 | them and not for a business administrator.
 |
 | This is a Dip Reset specific endpoint rather than a change to that check, so
 | Accounts stay closed everywhere else for the business - only this form, which
 | cannot function without the account, is served.
 |
 | It also applies a business_id filter the core method omits. That method reads
 |     Account::where('account_type_id', $account_type)
 | with no business scope, which on a multi-tenant install returns accounts
 | belonging to OTHER businesses. Worth raising with whoever maintains that file.
 */
Route::get('/dip-reset/inventory-adjustment-account', 'DipManagementController@getDipResetAdjustmentAccount')
    ->name('petrogeneral.dip_reset.adjustment_account');

Route::get('/get-tank-balance-by-id/{tank_id}', 'Dip\\TankBalanceController@show')
    ->where('tank_id', '[0-9]+')
    ->name('petrogeneral.dip_management.tank_balance');
