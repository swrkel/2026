<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Poultry module routes
|--------------------------------------------------------------------------
| All routes sit behind the application's standard web + auth middleware and
| are prefixed /poultry. Permission keys match Config/module_permissions.php,
| so the Roles screen gates every page listed here.
*/

/*
| IS2107: Poultry needs the same middleware stack as every other module.
|
| With only ['web','auth'] the module ran outside tenancy: a farm created on
| 2003.nivasa.shop was written to nivasa_base, the CENTRAL database, so every
| tenant would have shared one set of farms, batches and costs.
|
|   tenant.context   points the default connection at the tenant, which is what
|                    Poultry's models use - they declare no connection of their
|                    own, so they follow the default.
|   SetSessionData   sets session('business.id'), which BusinessContext::id()
|                    reads to stamp business_id on every record. Without it that
|                    value is null.
|   language,timezone  match the rest of the application.
*/
Route::group(['middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'], 'prefix' => 'poultry'], function () {

    Route::get('/', 'DashboardController@index')->name('poultry.dashboard');
    Route::get('/dashboard/alerts', 'DashboardController@alerts')->name('poultry.dashboard.alerts');

    /* ----------------------------- Batches ----------------------------- */
    Route::get('batches', 'BatchController@index')->name('poultry.batch.index');
    Route::get('batches/create', 'BatchController@create')->name('poultry.batch.create');
    Route::post('batches', 'BatchController@store')->name('poultry.batch.store');
    Route::get('batches/{id}', 'BatchController@show')->name('poultry.batch.show');
    Route::get('batches/{id}/edit', 'BatchController@edit')->name('poultry.batch.edit');
    Route::put('batches/{id}', 'BatchController@update')->name('poultry.batch.update');
    Route::post('batches/{id}/close', 'BatchController@close')->name('poultry.batch.close');
    Route::get('batches/{id}/transfer', 'BatchController@transferForm')->name('poultry.batch.transfer.form');
    Route::post('batches/{id}/transfer', 'BatchController@transfer')->name('poultry.batch.transfer');
    Route::get('batches-data', 'BatchController@data')->name('poultry.batch.data');
    Route::get('houses-by-farm/{farmId}', 'BatchController@housesByFarm')->name('poultry.batch.houses');

    /* -------------------------- Daily records -------------------------- */
    Route::get('daily', 'DailyRecordController@index')->name('poultry.daily.index');
    Route::get('daily/entry', 'DailyRecordController@entry')->name('poultry.daily.entry');
    Route::post('daily', 'DailyRecordController@store')->name('poultry.daily.store');
    Route::delete('daily/{id}', 'DailyRecordController@destroy')->name('poultry.daily.destroy');
    Route::get('daily-data', 'DailyRecordController@data')->name('poultry.daily.data');
    Route::post('weight-samples', 'DailyRecordController@storeWeightSample')->name('poultry.daily.weight');

    /* ------------------------- Egg collection -------------------------- */
    Route::get('eggs', 'EggCollectionController@index')->name('poultry.egg.index');
    Route::get('eggs/entry', 'EggCollectionController@entry')->name('poultry.egg.entry');
    Route::post('eggs', 'EggCollectionController@store')->name('poultry.egg.store');
    Route::get('eggs-data', 'EggCollectionController@data')->name('poultry.egg.data');

    /* ------------------------------ Feed ------------------------------- */
    Route::get('feed', 'FeedController@index')->name('poultry.feed.index');
    Route::get('feed/issue', 'FeedController@issueForm')->name('poultry.feed.issue.form');
    Route::post('feed', 'FeedController@store')->name('poultry.feed.store');
    Route::delete('feed/{id}', 'FeedController@destroy')->name('poultry.feed.destroy');
    Route::get('feed-data', 'FeedController@data')->name('poultry.feed.data');
    Route::get('feed/stock/{variationId}/{locationId}', 'FeedController@stock')->name('poultry.feed.stock');

    /* ----------------------------- Health ------------------------------ */
    Route::get('health', 'HealthController@index')->name('poultry.health.index');
    Route::get('health/batch/{id}', 'HealthController@batch')->name('poultry.health.batch');
    Route::post('health/vaccination', 'HealthController@storeVaccination')->name('poultry.health.vaccination.store');
    Route::post('health/treatment', 'HealthController@storeTreatment')->name('poultry.health.treatment.store');
    Route::get('health/withdrawals', 'HealthController@withdrawals')->name('poultry.health.withdrawals');

    /* ----------------------------- Harvest ----------------------------- */
    Route::get('harvest', 'HarvestController@index')->name('poultry.harvest.index');
    Route::get('harvest/create', 'HarvestController@create')->name('poultry.harvest.create');
    Route::post('harvest', 'HarvestController@store')->name('poultry.harvest.store');
    Route::get('harvest-data', 'HarvestController@data')->name('poultry.harvest.data');

    /* ---------------------------- Hatchery ----------------------------- */
    Route::get('hatchery', 'HatcheryController@index')->name('poultry.hatchery.index');
    Route::get('hatchery/create', 'HatcheryController@create')->name('poultry.hatchery.create');
    Route::post('hatchery', 'HatcheryController@store')->name('poultry.hatchery.store');
    Route::get('hatchery/{id}', 'HatcheryController@show')->name('poultry.hatchery.show');
    Route::post('hatchery/{id}/candle', 'HatcheryController@candle')->name('poultry.hatchery.candle');
    Route::post('hatchery/{id}/hatch', 'HatcheryController@hatch')->name('poultry.hatchery.hatch');

    /* ----------------------------- Masters ----------------------------- */
    Route::get('masters', 'MasterController@index')->name('poultry.master.index');
    Route::resource('farms', 'FarmController', ['as' => 'poultry']);
    Route::resource('houses', 'HouseController', ['as' => 'poultry']);
    Route::resource('breeds', 'BreedController', ['as' => 'poultry']);
    Route::resource('egg-grades', 'EggGradeController', ['as' => 'poultry']);
    Route::resource('vaccination-schedules', 'VaccinationScheduleController', ['as' => 'poultry']);

    /* ----------------------------- Reports ----------------------------- */
    Route::get('reports', 'ReportController@index')->name('poultry.report.index');
    Route::get('reports/performance', 'ReportController@performance')->name('poultry.report.performance');
    Route::get('reports/production', 'ReportController@production')->name('poultry.report.production');
    Route::get('reports/costing', 'ReportController@costing')->name('poultry.report.costing');
    Route::get('reports/mortality', 'ReportController@mortality')->name('poultry.report.mortality');

    /* ---------------------------- Settings ----------------------------- */
    Route::get('settings', 'SettingController@index')->name('poultry.settings');
    Route::post('settings', 'SettingController@update')->name('poultry.settings.update');
});
