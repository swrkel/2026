<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'module' => 'AirlineTicketingNew',
        'status' => 'ok',
    ]))->name('health');
});
