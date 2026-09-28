<?php
use Illuminate\Support\Facades\Route;
Route::view('gds/providers','airlineticketingnew::gds.providers.index')
    ->name('gds.providers.index')
    ->middleware('permission:airline_ticketing_new.gds.manage');
