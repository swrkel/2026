<?php
namespace Modules\StockTransferNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\StockTransferNew\Http\Controllers';
    public function boot(){ parent::boot(); }
}
