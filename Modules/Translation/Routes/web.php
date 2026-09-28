<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Translation Web Routes
|--------------------------------------------------------------------------
|
| Some installations do not have translation.route_group_config set in the
| config file. Laravel's Route::group() requires an array, so using the raw
| config value can throw:
|   Router::group(): Argument #1 ($attributes) must be of type array, null given
|
| Keep the existing route behaviour, but safely fall back to an empty group
| config when the setting is missing.
|
*/

$routeGroupConfig = config('translation.route_group_config', []);

if (! is_array($routeGroupConfig)) {
    $routeGroupConfig = [];
}

$uiUrl = config('translation.ui_url', 'languages');

if (! is_string($uiUrl) || trim($uiUrl) === '') {
    $uiUrl = 'languages';
}

Route::group($routeGroupConfig, function ($router) use ($uiUrl) {
    $router->get($uiUrl, 'LanguageController@index')
        ->name('languages.index');

    $router->post($uiUrl, 'LanguageController@list')
        ->name('languages.list');

    $router->get($uiUrl . '/create', 'LanguageController@create')
        ->name('languages.create');

    $router->post($uiUrl . '/store', 'LanguageController@store')
        ->name('languages.store');

    $router->get($uiUrl . '/{id}/edit', 'LanguageController@edit')
        ->name('languages.edit');

    $router->put($uiUrl . '/{id}', 'LanguageController@update')
        ->name('languages.update');

    $router->delete($uiUrl . '/{id}', 'LanguageController@destroy')
        ->name('languages.destroy');
});
