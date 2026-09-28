<?php

/*
 * 8055 - Standalone Subscription Module.
 *
 * Legacy tenant-local subscription routes were intentionally retired.  The
 * replacement works from the central context and can inspect selected tenant
 * databases without requiring tenancy to be initialized for the request.
 */
Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => 'subscription',
    'namespace' => 'Modules\\Subscription\\Http\\Controllers',
], function () {
    Route::get('/business-options', 'EstateSubscriptionController@businessOptions')->name('subscription.estate.business-options');
    Route::get('/', 'EstateSubscriptionController@index')->name('subscription.estate.index');
    Route::get('/create', 'EstateSubscriptionController@create')->name('subscription.estate.create');
    Route::post('/', 'EstateSubscriptionController@store')->name('subscription.estate.store');
    Route::get('/{id}/edit', 'EstateSubscriptionController@edit')->where('id', '[0-9]+')->name('subscription.estate.edit');
    Route::put('/{id}', 'EstateSubscriptionController@update')->where('id', '[0-9]+')->name('subscription.estate.update');
    Route::delete('/{id}', 'EstateSubscriptionController@destroy')->where('id', '[0-9]+')->name('subscription.estate.destroy');
});
