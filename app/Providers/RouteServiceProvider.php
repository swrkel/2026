<?php

namespace App\Providers;

use Closure;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PosHoldsFallbackController;
use App\Http\Controllers\GlobalDocumentOutputController;
use Modules\Customers\Http\Controllers\CustomerCompatibilityController;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to controller routes.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
        $this->mapManagementReportRoutes();

        // Suppliers is a tenant-owned standalone module, but copied application
        // roots can boot before Nwidart has populated the module route collection.
        // Register the canonical Supplier routes from the application provider that
        // is guaranteed to boot on Laravel 10. The module route file itself carries
        // tenant/auth/access middleware, so this does not bypass tenancy security.
        $this->mapStandaloneSuppliersRoutes();
    }

    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        // Finance List Accounts must be registered in the normal route-mapping
        // phase, not conditionally from boot().  This keeps it deterministic on
        // tenant installations and when route caching is rebuilt.
        $this->mapStandaloneFinanceAccountRoutes();
        $this->mapMyHealthPublicRoutes();
    }

    /**
     * Deterministic standalone Suppliers route loader.
     *
     * Normal Nwidart module discovery remains the primary path. This fallback
     * only runs when the canonical GET /suppliers URI is genuinely absent.
     * It is intentionally registered from boot() (not the legacy map() method),
     * which is the reliable Laravel 10 provider lifecycle used by this app.
     */
    protected function mapStandaloneSuppliersRoutes(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (trim((string) $route->uri(), '/') === 'suppliers'
                && in_array('GET', $route->methods(), true)) {
                return;
            }
        }

        $modulePath = base_path('Modules/Suppliers');
        $routeFile = $modulePath . '/Routes/web.php';

        if (! is_file($routeFile)) {
            return;
        }

        $viewPath = $modulePath . '/Resources/views';
        $langPath = $modulePath . '/Resources/lang';

        if (is_dir($viewPath)) {
            \Illuminate\Support\Facades\View::addNamespace('suppliers', $viewPath);
        }

        if (is_dir($langPath)) {
            app('translator')->addNamespace('suppliers', $langPath);
        }

        require $routeFile;
    }

    /**
     * Register central routes on one canonical central host only.
     *
     * Registering the full web/api route collections once for every configured
     * localhost alias multiplies the route table, slows every request and makes
     * named-route resolution unpredictable on tenant domains.
     */
    protected function mapOnCentralDomain(Closure $registrar): void
    {
        $domain = $this->canonicalCentralDomain();
        $registrar($domain);
    }

    protected function mapWebRoutes()
    {
        /*
         * Keep the legacy web collection registered only once, but do not bind
         * it to APP_URL/CENTRAL_DOMAIN. A number of existing tenant pages still
         * live in routes/web.php while the standalone migration is in progress.
         * Domain binding makes route() generate nivasa.shop links from tenant
         * hosts and therefore drops the tenant session on navigation.
         *
         * Tenant routes loaded by TenancyServiceProvider remain canonical for
         * duplicate route names; this change only keeps legacy compatibility
         * URLs on the current request host.
         */
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));


        // BUSINESS-MODULE-DATE-DEFAULTS-20260821
        // Dedicated tenant-safe routes for Business Settings -> Module Date Defaults.
        // Kept outside the large legacy routes/web.php so this feature can be deployed
        // without changing unrelated route definitions.
        $moduleDateDefaultsRoutes = base_path('routes/module_date_defaults.php');
        if (is_file($moduleDateDefaultsRoutes)) {
            Route::middleware([
                    'web',
                    'App\Http\Middleware\AutoLogoutMiddleware',
                    'IsInstalled',
                    'auth:customer,web',
                    'SetSessionData',
                    'DayEnd',
                    'language',
                    'timezone',
                    'bootstrap',
                    'isVerified',
                ])
                ->namespace($this->namespace)
                ->group($moduleDateDefaultsRoutes);
        }


        /*
         * Central/tenant-safe compatibility fallback.
         *
         * Some transferred installations can continue using an older route
         * collection while PHP OPcache or a server-level deployment cache is
         * being refreshed. Register the legacy opening-balance aliases here as
         * a final application-level guarantee. Tenant routes may register the
         * same aliases later; their tenant middleware remains authoritative.
         */
        if (! Route::has('contacts.import_balance.compat')) {
            Route::middleware([
                    'web',
                    'IsInstalled',
                    'bootstrap',
                    'language',
                    'dynamic.no-store',
                    'check.route.permission',
                ])
                ->get('/contacts/import-balance', [CustomerCompatibilityController::class, 'importBalance'])
                ->name('contacts.import_balance.compat');
        }

        if (! Route::has('contacts.import_balance.post.compat')) {
            Route::middleware([
                    'web',
                    'IsInstalled',
                    'bootstrap',
                    'language',
                    'dynamic.no-store',
                    'check.route.permission',
                ])
                ->post('/contacts/import-balance', [CustomerCompatibilityController::class, 'postImportBalance'])
                ->name('contacts.import_balance.post.compat');
        }

        /*
         * Hard fallback for the standalone POS Holds page.
         *
         * Some installations contain the POS module files but the module
         * provider is not registered, so none of Modules/POS/Routes/*.php is
         * loaded. Registering this route here guarantees that it appears in
         * route:list and works on tenant hosts. The application controller
         * adds the POS view/lang namespaces at runtime and does not depend on
         * POS module controller autoloading.
         */
        if (! Route::has('pos.holds.index')) {
            Route::middleware(['web', 'auth'])
                ->get('/pos-module/holds', [PosHoldsFallbackController::class, 'index'])
                ->name('pos.holds.index');
        }


        // Canonical PDF endpoint used by every current/future browser export.
        if (! Route::has('global.documents.pdf')) {
            Route::middleware(['web', 'tenant.context', 'auth', 'language', 'SetSessionData'])
                ->post('/global-documents/pdf', [GlobalDocumentOutputController::class, 'pdf'])
                ->name('global.documents.pdf');
        }
    }

    /**
     * Register the standalone Management Report through the application route
     * provider that is active on every central/tenant installation.
     */
    protected function mapManagementReportRoutes(): void
    {
        if (Route::has('managementreport.dashboard')) {
            return;
        }

        $modulePath = base_path('Modules/ManagementReport');
        $routeFile = $modulePath . '/Routes/web.php';
        if (!is_file($routeFile)) {
            return;
        }

        \Illuminate\Support\Facades\View::addNamespace(
            'managementreport',
            $modulePath . '/Resources/views'
        );
        app('translator')->addNamespace(
            'managementreport',
            $modulePath . '/Resources/lang'
        );

        foreach ([
            'managementreport' => 'config.php',
            'managementreport_menu' => 'menu.php',
            'managementreport_permissions' => 'permissions.php',
            'managementreport_sections' => 'sections.php',
        ] as $configKey => $configFile) {
            $configPath = $modulePath . '/Config/' . $configFile;
            if (is_file($configPath)) {
                config()->set(
                    $configKey,
                    array_replace_recursive(
                        (array) require $configPath,
                        (array) config($configKey, [])
                    )
                );
            }
        }

        if (!app()->bound(\Modules\ManagementReport\Services\Reports\SectionRegistry::class)) {
            app()->singleton(
                \Modules\ManagementReport\Services\Reports\SectionRegistry::class,
                static function ($app) {
                    return new \Modules\ManagementReport\Services\Reports\SectionRegistry(
                        $app,
                        config('managementreport_sections', [])
                    );
                }
            );
        }

        Route::middleware([
            'web',
            'auth',
            \Modules\ManagementReport\Http\Middleware\InitializeManagementReportTenant::class,
            \Modules\ManagementReport\Http\Middleware\ActivateManagementReportTenant::class,
        ])->group($routeFile);
    }


    /**
     * Canonical Finance > List Accounts loader.
     *
     * Registered after the normal route collections so /finance/account wins
     * over stale compatibility/module definitions on transferred tenants.
     */
    protected function mapStandaloneFinanceAccountRoutes(): void
    {
        $modulePath = base_path('Modules/Finance');
        $routeFile = $modulePath . '/Routes/standalone_accounts.php';
        $controllerFile = $modulePath . '/Http/Controllers/StandaloneAccountController.php';
        $viewPath = $modulePath . '/Resources/views';

        if (! is_file($routeFile) || ! is_file($controllerFile)) {
            return;
        }

        // Do not depend on the Finance module provider/autoloader having run
        // before the application RouteServiceProvider.  On some tenant boots it
        // has not, class_exists() returned false, and /finance/account was never
        // registered.  Loading this one controller explicitly makes the route
        // registration deterministic while keeping the implementation inside
        // Modules/Finance.
        $controllerClass = \Modules\Finance\Http\Controllers\StandaloneAccountController::class;
        if (! class_exists($controllerClass, false)) {
            require_once $controllerFile;
        }

        if (! class_exists($controllerClass, false)) {
            \Illuminate\Support\Facades\Log::error('Finance standalone List Accounts controller could not be loaded.', [
                'controller_file' => $controllerFile,
            ]);
            return;
        }

        if (is_dir($viewPath)) {
            \Illuminate\Support\Facades\View::addNamespace('finance', $viewPath);
        }

        Route::middleware([
            'web',
            'auth',
            'SetSessionData',
            'language',
            'timezone',
            'tenant.context',
        ])->group($routeFile);
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Public My Health routes are mapped here once per central domain.
     * The module provider checks these route names and therefore skips its
     * fallback registration when this canonical loader has already run.
     */
    protected function mapMyHealthPublicRoutes()
    {
        $path = base_path('Modules/MyHealthMembers/Routes/public.php');
        if (! file_exists($path)) {
            return;
        }

        $this->mapOnCentralDomain(function ($domain) use ($path): void {
            $route = Route::middleware('web');

            if ($domain !== null) {
                $route->domain($domain);
            }

            $route->group($path);
        });
    }

    protected function canonicalCentralDomain(): ?string
    {
        $domains = array_values(array_unique(array_filter(
            (array) config('tenancy.central_domains', [])
        )));

        if ($domains === []) {
            return null;
        }

        foreach ($domains as $domain) {
            if (! in_array($domain, ['localhost', '127.0.0.1'], true)) {
                return $domain;
            }
        }

        return $domains[0];
    }
}
