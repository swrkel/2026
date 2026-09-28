<?php

use Illuminate\Support\Facades\Route;
use Modules\Ran\Http\Controllers\PublicShareController;

Route::get('ran/share/{token}', [PublicShareController::class, 'show'])->name('ran.public.share');
Route::get('ran/share/{token}/download', [PublicShareController::class, 'download'])->name('ran.public.download');
