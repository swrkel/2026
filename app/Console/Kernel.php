<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

         'App\Console\Commands\DatabaseBackUp',
         'App\Console\Commands\ExpiredModules',
         '\App\Console\Commands\BackupCronCommand',
         'App\Console\Commands\DeletePrevBackups',
         'App\Console\Commands\SyncTenantDatabases',
         'App\Console\Commands\DetectDatabaseChanges',
         'App\Console\Commands\CleanupExpiredGraceBusinesses',
         'App\Console\Commands\LegacyBaselineCommand',
         'App\Console\Commands\LegacyBaselineAutoCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected function schedule(Schedule $schedule)
    {
        $env = config('app.env');

        $email = config('mail.username');

        /*
        |--------------------------------------------------------------------------
        | LIVE ENVIRONMENT
        |--------------------------------------------------------------------------
        */

        if ($env === 'live') {

            /*
            |--------------------------------------------------------------------------
            | Backup Scheduling
            |--------------------------------------------------------------------------
            */

            $schedule->command(
                'backup:run'
            )->dailyAt('23:30');

            $schedule->command(
                'backups:delete-old'
            )->dailyAt('23:30');

            /*
            |--------------------------------------------------------------------------
            | Subscription Automation
            |--------------------------------------------------------------------------
            */

            $schedule->command(
                'pos:generateSubscriptionInvoices'
            )->daily();

            $schedule->command(
                'pos:updateRewardPoints'
            )->daily();
        }

        /*
        |--------------------------------------------------------------------------
        | Demo Environment
        |--------------------------------------------------------------------------
        */

        if ($env === 'demo' && !empty($email)) {

            $schedule->command(
                    'pos:dummyBusiness'
                )
                ->cron('0 */3 * * *')
                ->emailOutputTo($email);
        }

        /*
        |--------------------------------------------------------------------------
        | Tenant Synchronization
        |--------------------------------------------------------------------------
        */

        $schedule->command(
            'tenants:sync'
        )->everyFiveMinutes();

        /*
        |--------------------------------------------------------------------------
        | Expired Modules
        |--------------------------------------------------------------------------
        */

        $schedule->command(
            'expired-modules'
        )->dailyAt('8:00');

        /*
        |--------------------------------------------------------------------------
        | Subscription Cleanup
        |--------------------------------------------------------------------------
        */

        $schedule->command(

            env(
                'SUBSCRIPTION_GRACE_AUTO_DELETE',
                false
            )

                ? 'subscription:cleanup-expired-grace --delete-data'

                : 'subscription:cleanup-expired-grace'

        )->dailyAt('00:30');

        /*
        |--------------------------------------------------------------------------
        | Database Change Detection
        |--------------------------------------------------------------------------
        */

        $schedule->command(
            'db:detect-changes'
        )->dailyAt('23:30');

        /*
        |--------------------------------------------------------------------------
        | SMS Reminders
        |--------------------------------------------------------------------------
        */

        $schedule->command(
            'sms:send-departure-reminders'
        )->dailyAt('09:00');

        /*
        |--------------------------------------------------------------------------
        | Subscription Expiry
        |--------------------------------------------------------------------------
        */

        $schedule->command(
            'subscription:check-expiry'
        )->daily();

        /*
        |--------------------------------------------------------------------------
        | ENTERPRISE LOAN DELINQUENCY ENGINE
        |--------------------------------------------------------------------------
        */

        $schedule->command(
            'loan:process-overdue'
        )
        ->dailyAt('00:30')
        ->withoutOverlapping()
        ->runInBackground();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */

    protected function commands()
    {
        $this->load(
            __DIR__.'/Commands'
        );

        require base_path(
            'routes/console.php'
        );
    }
}