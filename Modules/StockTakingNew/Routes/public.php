<?php
use Illuminate\Support\Facades\Route;
use Modules\StockTakingNew\Http\Controllers\PublicShareController;
Route::get('/shared/{token}',[PublicShareController::class,'show'])->name('shared.show');
Route::get('/shared/{token}/download',[PublicShareController::class,'download'])->name('shared.download');
