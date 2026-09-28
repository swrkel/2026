<?php

namespace Modules\HelpGuide\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            if (! isAppInstalled()) {
                return;
            }

            $services = Config::get('services', []);

            foreach (['envato', 'facebook', 'google'] as $provider) {
                if (setting($provider . '_oauth_enabled', false)) {
                    $services[$provider] = [
                        'client_id' => setting($provider . '_oauth_app_id'),
                        'client_secret' => setting($provider . '_oauth_app_secret'),
                        'redirect' => url('login/' . $provider . '/callback'),
                    ];
                }
            }

            Config::set('services', $services);
            Config::set('app.debug', (bool) setting('app_debug', defaultSetting('app_debug', false)));
        } catch (\Throwable $e) {
            // Database-backed HelpGuide settings are optional during bootstrap.
        }
    }
}