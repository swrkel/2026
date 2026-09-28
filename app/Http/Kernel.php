<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \App\Http\Middleware\InjectGlobalReportsPagesFooter::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // \App\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\TenantSessionCookie::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\AutoLogoutMiddleware::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\UserLocationAccess::class,
            // Role permissions must be enforced on every authenticated web route,
            // including legacy/standalone routes that do not declare the alias.
            \App\Http\Middleware\CheckRoutePermission::class,
            \App\Http\Middleware\EnforceBusinessSidebarModuleAccess::class,
            \App\Http\Middleware\EnforceSubscriptionGraceReadOnly::class,
            // S714: deterministic, no-animation sidebar open/close + /home heading.
            \App\Http\Middleware\ApplySidebarUiFix::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
            \App\Http\Middleware\EnforceBusinessSidebarModuleAccess::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // 'throttle:api',
            // \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'public' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetUserLocale::class,
            \App\Http\Middleware\CheckForMaintenanceMode::class
        ],

        'install' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocale::class
        ],

        'dashboard' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\SetUserLocale::class,
            \App\Http\Middleware\CheckForMaintenanceMode::class,
        ],

        'my_account' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\SetUserLocale::class,
            \App\Http\Middleware\CheckForMaintenanceMode::class,
        ],

        'vendor_web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckForMaintenanceMode::class,
            \App\Http\Middleware\EnforceSubscriptionGraceReadOnly::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'borrower' => \App\Http\Middleware\BorrowerMiddleware::class,
        'recovery.officer' => \App\Http\Middleware\RecoveryOfficerMiddleware::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'language' => \App\Http\Middleware\Language::class,
        'timezone' => \App\Http\Middleware\Timezone::class,
        'SetSessionData' => \App\Http\Middleware\SetSessionData::class,
        'authh' => \App\Http\Middleware\IsInstalled::class,
        'IsInstalled' => \App\Http\Middleware\IsInstalled::class,
        'bootstrap' => \App\Http\Middleware\Callbacks::class,
        'AdminSidebarMenu' => \App\Http\Middleware\AdminSidebarMenu::class,
        'BusinessSidebarAccess' => \App\Http\Middleware\EnforceBusinessSidebarModuleAccess::class,
        'EcomApi' => \App\Http\Middleware\EcomApi::class,
        'DayEnd' => \App\Http\Middleware\DayEnd::class,
        'tenant.context' => \App\Http\Middleware\SetTenantContext::class,
        'IsSubscribed' => \App\Http\Middleware\CheckSubscribed::class,
        'SubscriptionGraceReadOnly' => \App\Http\Middleware\EnforceSubscriptionGraceReadOnly::class,
         'isVerified' =>  \App\Http\Middleware\isVerified::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        'admin_only' => \App\Http\Middleware\AdminOnly::class,
        'set_locale' => \App\Http\Middleware\SetLocale::class,
        'frontend' => \App\Http\Middleware\Frontend::class,
        'check.route.permission' => \App\Http\Middleware\CheckRoutePermission::class,
        'dynamic.no-store' => \App\Http\Middleware\NoStoreDynamicResponse::class,
        'myhealth.member.portal' => \Modules\MyHealthMembers\Http\Middleware\MyHealthMemberPortalAuth::class,
        'myhealthmembers.member.portal' => \Modules\MyHealthMembers\Http\Middleware\MyHealthMemberPortalAuth::class,
        'myhealth.mobile' => \Modules\MyHealthMembers\Http\Middleware\MyHealthMobileAuth::class,
    ];
    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    /*
     |--------------------------------------------------------------------------
     | Middleware priority
     |--------------------------------------------------------------------------
     |
     | IS1966: Save Draft returned
     |
     |     SQLSTATE[42S02] Table 'nivasa_base.san_stock_adjustments' doesn't exist
     |     select * from `san_stock_adjustments` where `id` = 1 limit 1
     |
     | Note the database: `nivasa_base` is the CENTRAL database, not the tenant.
     | And the query shape - "where id = ? limit 1" - is route-model binding.
     |
     | SubstituteBindings lives inside the `web` group, so it runs as part of
     | that group. StockAdjustmentNew registers its routes as
     | ['web', InitializeStockAdjustmentTenantContext, 'auth', ...], but listing
     | middleware in that order does not guarantee execution order - Laravel
     | sorts by this priority list, and the module's own tenant middleware was
     | not in it. So bindings could resolve BEFORE tenancy initialised, against
     | the central connection, where the tenant's tables do not exist.
     |
     | SetTenantContext was already listed ahead of SubstituteBindings for
     | exactly this reason; the module's equivalent simply needed the same
     | treatment.
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\SetTenantContext::class,
        // Finance supports both central-database businesses and Stancl tenants.
        // Resolve that context before auth, permissions or route bindings query.
        \Modules\Finance\Http\Middleware\InitializeFinanceTenantContext::class,
        \Modules\StockAdjustmentNew\Http\Middleware\InitializeStockAdjustmentTenantContext::class,
        /*
         * LA-1157: same reason as the line above. Products New registers its
         * routes as ['web', InitializeProductsNewTenantContext, 'auth',
         * 'check.route.permission'], but that listing does not control execution
         * order - this list does. Without an entry here its tenant middleware had
         * no guaranteed position relative to SubstituteBindings, so route-model
         * binding could run against the CENTRAL database, where the tenant's
         * product tables do not exist, and the page would not open.
         */
        \Modules\ProductsNew\Http\Middleware\InitializeProductsNewTenantContext::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
