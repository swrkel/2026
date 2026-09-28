<?php

namespace Modules\HelpGuide\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
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

            $mail = Config::get('mail', []);

            if (setting('mail_channel') === 'smtp') {
                $mail['driver'] = 'smtp';
                $mail['host'] = setting('smtp_host');
                $mail['port'] = setting('smtp_port');
                $mail['encryption'] = setting('smtp_encryption');
                $mail['username'] = setting('smtp_username');
                $mail['password'] = setting('smtp_password');
            }

            $mail['from'] = [
                'address' => setting('mail_from_address', defaultSetting('mail_from_address')),
                'name' => setting('mail_from_name', 'Ticky app'),
            ];

            Config::set('mail', $mail);
        } catch (\Throwable $e) {
            // Keep configured defaults when settings storage is unavailable.
        }
    }
}