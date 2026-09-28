<?php

use Illuminate\Support\Facades\Route;

if (! defined('SALES_AGENT_MODULE_ROUTES_LOADED')) {
    define('SALES_AGENT_MODULE_ROUTES_LOADED', true);

/*
|--------------------------------------------------------------------------
| SalesAgent Standalone Module Routes
|--------------------------------------------------------------------------
|
| Route definitions stay inside this module route file.
|
*/

Route::group([
    'middleware' => [
        'auth:customer,web',
        'SetSessionData',
        'language',
        'timezone',
        'bootstrap',
        'check.route.permission',
    ],
    'prefix' => 'salesagent',
    'as' => 'salesagent.',
    'namespace' => '\\Modules\\SalesAgent\\Http\\Controllers',
], function () {
    Route::get('/management', 'SalesAgentManagementController@index')
        ->name('management.index');

    Route::get('/management/data', 'SalesAgentManagementController@getSalesAgentsData')
        ->name('management.data');

    Route::post('/management/store', 'SalesAgentManagementController@store')
        ->name('management.store');

    Route::post('/management/commission', 'SalesAgentManagementController@storeCommission')
        ->name('management.commission.store');

    Route::get('/management/{id}/ledger', 'SalesAgentManagementController@ledger')
        ->name('management.ledger');

    Route::get('/management/{id}/edit', 'SalesAgentManagementController@edit')
        ->name('management.edit');

    Route::put('/management/{id}', 'SalesAgentManagementController@update')
        ->name('management.update');

    Route::delete('/management/{id}', 'SalesAgentManagementController@destroy')
        ->name('management.destroy');

    Route::get('/management/{id}', 'SalesAgentManagementController@show')
        ->name('management.show');
});

}
