<?php

namespace Modules\HelpGuide\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\HelpGuide\Console\Commands\ProcessArticleTranslations;

class HelpGuideServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'helpguide');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'helpguide');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessArticleTranslations::class,
            ]);

            /*
             * Translation runs in tiny background batches. The normal Laravel
             * scheduler cron (php artisan schedule:run every minute) is enough;
             * there is no long-running queue worker and no dependency on the
             * application's global QUEUE_CONNECTION.
             */
            $this->app->booted(function (): void {
                try {
                    $schedule = $this->app->make(Schedule::class);
                    $limit = max(1, min((int) config('helpguide.translation.scheduler_batch_size', 1), 10));
                    $schedule->command('helpguide:translate-pending --limit=' . $limit)
                        ->everyMinute()
                        ->withoutOverlapping(15);
                } catch (\Throwable $e) {
                    // A web request never depends on scheduler registration.
                    report($e);
                }
            });
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'helpguide');
    }
}
