<?php

namespace Modules\Essentials\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\Essentials\Entities\EssentialsAttendance;
use App\Utils\ModuleUtil;
use Illuminate\Console\Scheduling\Schedule;
use App\Services\GlobalProviderViewCache;

class EssentialsServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        View::composer(
            ['essentials::layouts.partials.header_part', 'report.profit_loss'],
            function ($view) {
                static $enabled = null;

                if ($enabled === null) {
                    $enabled = false;
                    try {
                        if (auth()->check()) {
                            $moduleUtil = new ModuleUtil();
                            if (auth()->user()->can('superadmin')) {
                                $enabled = $moduleUtil->isModuleInstalled('Essentials');
                            } else {
                                $businessId = session('user.business_id');
                                $enabled = $businessId
                                    ? (bool) $moduleUtil->hasThePermissionInSubscription($businessId, 'essentials_module')
                                    : false;
                            }
                        }
                    } catch (\Throwable $e) {
                        $enabled = false;
                    }
                }

                $view->with('__is_essentials_enabled', $enabled);
            }
        );

        View::composer(['essentials::layouts.partials.header_part'], function ($view) {
            static $resolved = false;
            static $isEmployeeAllowed = false;
            static $clockIn = null;

            if (! $resolved) {
                $resolved = true;

                try {
                    if (auth()->check() && ! $this->app->runningInConsole()) {
                        $moduleUtil = new ModuleUtil();
                        if ($moduleUtil->isModuleInstalled('Essentials')) {
                            $businessId = session('user.business_id');
                            $isEmployeeAllowed = auth()->user()->can('essentials.allow_users_for_attendance_from_web');

                            if ($businessId) {
                                $userId = (int) auth()->id();
                                $clockIn = GlobalProviderViewCache::remember(
                                    'essentials_open_attendance',
                                    [(int) $businessId, $userId],
                                    10,
                                    static function () use ($businessId, $userId) {
                                        return EssentialsAttendance::query()
                                            ->where('essentials_attendances.business_id', $businessId)
                                            ->leftJoin('essentials_shifts as es', 'es.id', '=', 'essentials_attendances.essentials_shift_id')
                                            ->where('user_id', $userId)
                                            ->whereNull('clock_out_time')
                                            ->select(['clock_in_time', 'es.name as shift_name', 'es.start_time', 'es.end_time'])
                                            ->first();
                                    }
                                );
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $isEmployeeAllowed = false;
                    $clockIn = null;
                }
            }

            $view->with([
                'is_employee_allowed' => $isEmployeeAllowed,
                'clock_in' => $clockIn,
            ]);
        });

        View::composer(
            ['essentials::attendance.clock_in_clock_out_modal', 'essentials::attendance.create'],
            function ($view) {
                $util = new \App\Utils\Util();
                $settings = session('business.essentials_settings');
                $settings = ! empty($settings) ? json_decode($settings, true) : [];

                $view->with([
                    'ip_address' => $util->getUserIpAddr(),
                    'is_location_required' => ! empty($settings['is_location_required']),
                ]);
            }
        );

        $this->registerScheduleCommands();
    
    }

    public function registerScheduleCommands()
    {
        $env = config('app.env');
        //schedule command for auto clock out user
        if ($env === 'live') {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('pos:autoClockOutUser')->everyThirtyMinutes();
            });
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('essentials.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'essentials'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/essential');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $viewPaths = array_values(array_filter(
            array_merge(array_map(function ($path) {
            return $path.'/modules/essential';
        }, config('view.paths')), [$sourcePath]),
            'is_dir'
        ));

        $this->loadViewsFrom($viewPaths, 'essentials');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/essential');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'essentials');
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'essentials');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__.'/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            \Modules\Essentials\Console\AutoClockOutUser::class,
        ]);
    }
}
