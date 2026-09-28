<?php

namespace Modules\BankingCoreDeposits\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('banking/core-deposits')
            ->name('banking.core-deposits.')
            ->group(module_path('BankingCoreDeposits', 'Routes/web.php'));
    }
}
