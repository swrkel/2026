<?php

namespace Modules\BankingRisk\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        Route::middleware(['web','auth'])->group(__DIR__.'/../Routes/web.php');
    }
}
