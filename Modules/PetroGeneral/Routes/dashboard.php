<?php

use Illuminate\Support\Facades\Route;

Route::get('/petro-dashboard', 'PetroDashboard\PetroDashboardController@index')
    ->name('petrogeneral.petro_dashboard.index');

Route::get('/dashboard', 'Dashboard\\DashboardController@index')
    ->name('petrogeneral.dashboard.index');
