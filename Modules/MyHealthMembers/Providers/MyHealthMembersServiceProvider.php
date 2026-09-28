<?php

namespace Modules\MyHealthMembers\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\MyHealthMembers\Http\Middleware\MyHealthMobileAuth;
use Modules\MyHealthMembers\Http\Middleware\MyHealthMemberPortalAuth;
use Modules\MyHealthMembers\Http\Middleware\MyHealthSidebarInjector;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Services\Support\MyHealthSidebarRegistrar;
use Modules\MyHealthMembers\Services\Support\MyHealthPortalBranding;

class MyHealthMembersServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'MyHealthMembers';
    protected string $moduleNameLower = 'myhealthmembers';

    /** Prevent the public fallback route file being loaded repeatedly. */
    protected static bool $publicRoutesFallbackRegistered = false;

    public function boot(): void
    {
        $this->registerMyHealthMiddlewareAliases();
        // Many ERP sidebar layouts are hard-coded and do not read module menu config.
        // This middleware safely injects the MyHealth menu into rendered HTML sidebars.
        $this->app['router']->pushMiddlewareToGroup('web', MyHealthSidebarInjector::class);
        // Register public routes early. The login/signup screen is outside the authenticated
        // module area, so relying only on the module RouteServiceProvider can leave
        // POST /myhealth-register unavailable in some ERP installations.
        $this->registerPublicRoutesFallback();

        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->registerSidebarMenu();
        $this->registerPortalBrandingComposer();

        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->registerMyHealthMiddlewareAliases();
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/menu.php'), $this->moduleNameLower . '_menu');
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/sidebar.php'), $this->moduleNameLower . '_sidebar');
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/permissions.php'), $this->moduleNameLower . '_permissions');
    }


    protected function registerMyHealthMiddlewareAliases(): void
    {
        if (! isset($this->app['router'])) {
            return;
        }

        $router = $this->app['router'];
        $router->aliasMiddleware('myhealth.mobile', MyHealthMobileAuth::class);
        $router->aliasMiddleware('myhealth.member.portal', MyHealthMemberPortalAuth::class);
        $router->aliasMiddleware('myhealthmembers.member.portal', MyHealthMemberPortalAuth::class);
    }

    protected function registerPublicRoutesFallback(): void
    {
        if (static::$publicRoutesFallbackRegistered) {
            return;
        }

        static::$publicRoutesFallbackRegistered = true;

        $publicRoutes = module_path($this->moduleName, 'Routes/public.php');

        if (! file_exists($publicRoutes)) {
            return;
        }

        // The host App\Providers\RouteServiceProvider is the canonical loader for
        // public My Health routes. Keep this fallback only for installations that do not
        // have that provider, and never register a second copy in the same boot cycle.
        if (! app()->providerIsLoaded(\App\Providers\RouteServiceProvider::class)
            && ! Route::has('myhealth.public.register.store')) {
            Route::middleware(['web'])->group($publicRoutes);
        }
    }

    protected function registerSidebarMenu(): void
    {
        Blade::include($this->moduleNameLower . '::partials.sidebar', 'myHealthMembersSidebar');

        $menu = MyHealthSidebarRegistrar::menu();
        $partial = MyHealthSidebarRegistrar::sidebarPartial();

        // Share globally for layouts that read module menus directly without a composer.
        View::share('myhealthMembersMenu', $menu);
        View::share('myHealthMembersMenu', $menu);
        View::share('myhealthSidebarMenu', $menu);
        View::share('myhealthMembersSidebarPartial', $partial);
        View::share('myHealthMembersSidebarPartial', $partial);

        $sidebarComposer = function ($view) {
            $menu = MyHealthSidebarRegistrar::menu();
            $partial = MyHealthSidebarRegistrar::sidebarPartial();

            $existingMenus = $view->offsetExists('module_menus') ? (array) $view->offsetGet('module_menus') : [];
            $existingMenus['myhealthmembers'] = $menu;

            $existingSidebarPartials = $view->offsetExists('module_sidebar_partials') ? (array) $view->offsetGet('module_sidebar_partials') : [];
            $existingSidebarPartials['myhealthmembers'] = $partial;

            // Multiple variable names are intentionally provided because ERP installations
            // use different sidebar builders. This keeps MyHealth visible without editing
            // working sidebar code when the layout supports dynamic module menus.
            $view->with('myhealthMembersMenu', $menu);
            $view->with('myHealthMembersMenu', $menu);
            $view->with('myhealthSidebarMenu', $menu);
            $view->with('myhealthMembersSidebarPartial', $partial);
            $view->with('myHealthMembersSidebarPartial', $partial);
            $view->with('module_menus', $existingMenus);
            $view->with('module_sidebar_partials', $existingSidebarPartials);
        };

        View::composer('*', $sidebarComposer);

        View::composer([
            'layouts.partials.sidebar',
            'layouts.partials.left_sidebar',
            'layouts.partials.side_bar',
            'layouts.partials.sidebar-menu',
            'layouts.partials.menu',
            'layouts.sidebar',
            'partials.sidebar',
            'partials.left_sidebar',
            'home.partials.sidebar',
        ], $sidebarComposer);
    }

    protected function registerPortalBrandingComposer(): void
    {
        View::composer('myhealthmembers::portal.*', function ($view) {
            $view->with('portal_branding', MyHealthPortalBranding::forCurrentPortalMember());
        });
    }

}

