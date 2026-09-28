<?php

namespace Modules\BankingTellerOperations\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\\BankingCoreTeller\\Http\\Controllers';

    public function boot(): void
    {
        parent::boot();
    }
}
