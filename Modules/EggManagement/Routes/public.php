<?php
use Illuminate\Support\Facades\Route;
use Modules\EggManagement\Http\Controllers\ShareController;
Route::get('egg/share/{token}', [ShareController::class,'publicView'])->name('egg.share.public');
