<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Development\Http\Controllers\SettingsController;
use Modules\Development\Http\Controllers\AddDevelopmentController;
use Modules\Development\Http\Controllers\ListDevelopmentController;

Route::middleware(['web', 'auth', 'language', 'tenant.context'])->prefix('/developments')->group(function () {
    Route::controller(SettingsController::class)->prefix('/settings')->name('development.settings.')->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{module}/edit', 'edit')->name('edit');
        Route::put('/{module}', 'update')->name('update');
        Route::delete('/{module}', 'destroy')->name('destroy');
    });

    Route::controller(AddDevelopmentController::class)->name('development.')->group(function(){
        Route::get('/create', 'create')->name('create');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::post('/save-doc-no', 'saveDocNo')->name('save-doc-no');
        Route::get('/{development}/edit', 'edit')->name('edit');
        Route::put('/{development}', 'update')->name('update');
        Route::delete('/{development}', 'destroy')->name('destroy');
        Route::post('/{development}/add-comment', 'addComment')->name('add-comment');
        Route::get('/related-docs',  'getRelatedDocs')->name('getrelated.docs');
        Route::post('/upload-image',  'uploadImage')->name('upload-image');

    });

    Route::prefix('/list-development')->controller(ListDevelopmentController::class)->name('list-development.')->group(function(){
        Route::get('/', 'index')->name('index');
    });
});