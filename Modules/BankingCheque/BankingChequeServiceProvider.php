<?php
namespace Modules\BankingCheque;
use Illuminate\Support\ServiceProvider;
class BankingChequeServiceProvider extends ServiceProvider { public function boot(){ $this->loadRoutesFrom(__DIR__.'/Routes/web.php'); $this->loadViewsFrom(__DIR__.'/Resources/views','bankingcheque'); $this->loadTranslationsFrom(__DIR__.'/Resources/lang','bankingcheque'); $this->loadMigrationsFrom(__DIR__.'/Database/Migrations'); } public function register(){ $this->mergeConfigFrom(__DIR__.'/Config/config.php','bankingcheque'); } }
