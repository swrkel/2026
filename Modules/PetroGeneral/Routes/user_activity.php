<?php

use Illuminate\Support\Facades\Route;

Route::get('/user-activity-general', 'UserActivity\\ActivityListController@index')
    ->name('petrogeneral.user_activity.index');
Route::get('/user-activity-general/export', 'UserActivity\\ActivityExportController@export')
    ->name('petrogeneral.user_activity.export');
