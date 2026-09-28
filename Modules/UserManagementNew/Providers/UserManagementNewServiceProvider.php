<?php

namespace Modules\UserManagementNew\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\UserManagementNew\Http\Middleware\EnforceManagedRolePermissions;

class UserManagementNewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'usermanagementnew');
    }

    public function boot(Router $router): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'usermanagementnew');

        /*
         * MA-002 (S-628): 'tenant.context' ADDED.
         *
         * Without it these routes run on the CENTRAL database. So Add User
         * wrote its rows into nivasa_base while login reads the tenant
         * database - proven by two logs on the same afternoon:
         *
         *   create:  user_id 53, username_stored "Test23-03", status active
         *   login :  row_found false, db_in_use "nivasa_ishadi"
         *
         * and by finding both Test23-03 and sonali12-03 sitting in
         * nivasa_base, on business 3, active and not deleted.
         *
         * That is why the screen reported success and the user still could not
         * log in: the row was created perfectly, in the wrong database.
         *
         * 52 other route files in this system already carry this middleware -
         * PetroPD, Finance and the rest. This module was the exception.
         *
         * 'SetSessionData' is included for the same reason it accompanies
         * tenant.context everywhere else: it is what populates
         * session('user.business_id'), which this controller reads to decide
         * which business a new user belongs to.
         */
        Route::middleware(['web', 'auth', 'SetSessionData', 'tenant.context', 'check.route.permission'])
            ->prefix('user-management-new')
            ->name('user-management-new.')
            ->group(__DIR__ . '/../Routes/web.php');

        // Enforce roles produced by this module across every authenticated
        // business route. Unmanaged legacy roles remain backward compatible.
        $router->pushMiddlewareToGroup('web', EnforceManagedRolePermissions::class);
    }
}
