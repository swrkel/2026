<?php

use Illuminate\Support\Facades\Route;

/*
 |-----------------------------------------------------------------------------
 | "DataTables warning: Invalid JSON response" on the Tank Transfers grid.
 |-----------------------------------------------------------------------------
 |
 | This route pointed at TransferListController@index, a wrapper that only ever
 | returns a Blade view:
 |
 |     return view('petrogeneral::tank_transfer.index', $data);
 |
 | The grid on Tank Management calls this same URL over ajax expecting JSON, so it
 | received a full HTML page. DataTables cannot parse that and reports "Invalid
 | JSON response" without saying what arrived.
 |
 | TankTransferController@index is the original and handles BOTH: it returns the
 | DataTables JSON for an ajax request and the page otherwise - see the IS2001
 | note in that method, which was written for exactly this symptom.
 |
 | Pointing the route back at it fixes the grid and leaves the page unchanged.
 | The route NAME is untouched, so every link and route() call still resolves.
 */
Route::get('/tank-transfers-general', 'TankTransferController@index')
    ->name('petrogeneral.tank_transfer.index');
Route::get('/tank-transfers-general/create', 'TankTransfer\\TransferCreateController@create')
    ->name('petrogeneral.tank_transfer.create');
// MA004: store route was never registered, so TankTransferController@store was
// unreachable and Form::open(action(...@store)) could not resolve a URL.
Route::post('/tank-transfers-general', 'TankTransferController@store')
    ->name('petrogeneral.tank_transfer.store');

Route::get('/tank-transfers-general/{id}', 'TankTransfer\\TransferViewController@show')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.tank_transfer.show');
