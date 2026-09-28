<?php

use Illuminate\Support\Facades\Route;
use Modules\DigitalBusinessCards\Http\Controllers\CardController;
use Modules\DigitalBusinessCards\Http\Controllers\PublicCardController;

$admin  = config('digital-business-cards.routes.admin');
$public = config('digital-business-cards.routes.public');

Route::group([
    'prefix'     => $admin['prefix'],
    'middleware' => $admin['middleware'],
    'as'         => $admin['as'],
], function () {
    Route::get('/',              [CardController::class, 'index'])->name('index');
    Route::get('/create',        [CardController::class, 'create'])->name('create');
    Route::post('/',             [CardController::class, 'store'])->name('store');
    Route::get('/{card}/edit',   [CardController::class, 'edit'])->name('edit');
    Route::put('/{card}',        [CardController::class, 'update'])->name('update');
    Route::delete('/{card}',     [CardController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix'     => $public['prefix'],
    'middleware' => $public['middleware'],
    'as'         => $public['as'],
], function () {
    Route::get('/{slug}',        [PublicCardController::class, 'show'])->name('show');
    Route::get('/{slug}/vcard',  [PublicCardController::class, 'vcard'])->name('vcard');
    Route::get('/{slug}/qr',     [PublicCardController::class, 'qr'])->name('qr');
});
