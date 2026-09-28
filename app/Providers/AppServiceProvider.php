<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Business;
use App\Currency;
use App\UserStorePermission;
use App\Utils\SidebarPermissionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\StreamHandler;
use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use App\Services\GlobalPerformanceCache;
use App\Services\GlobalProviderViewCache;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot(Request $request)
  {
    Event::listen(\Stancl\Tenancy\Events\TenancyInitialized::class, function () { $g = auth()->guard(); if (method_exists($g, "forgetUser") && $g->check()) { $g->forgetUser(); } });
    /*
    |--------------------------------------------------------------------------
    | Request-host URL generation
    |--------------------------------------------------------------------------
    |
    | This ERP serves the same Laravel application from the central domain and
    | many tenant domains. APP_URL points to the central application, so URL
    | generation must never use it as the root during an active web request.
    |
    | Force route(), action(), url(), redirects and form actions to use the host
    | that the browser actually requested. Relative paths and explicit external
    | URLs are unaffected. This keeps normal clicks, Ctrl+Click, copied links,
    | bookmarks and new tabs inside the current tenant domain.
    |
    */
    if (! app()->runningInConsole()) {
      $forwardedProto = (string) $request->headers->get('X-Forwarded-Proto', '');
      $scheme = $forwardedProto !== ''
        ? strtolower(trim(explode(',', $forwardedProto)[0]))
        : $request->getScheme();

      if (! in_array($scheme, ['http', 'https'], true)) {
        $scheme = $request->isSecure() ? 'https' : 'http';
      }

      $host = $request->getHttpHost();
      if ($host !== '') {
        URL::forceRootUrl($scheme . '://' . $host);
        URL::forceScheme($scheme);
      }
    }

    if (request()->has('lang')) {
      \App::setLocale(request()->get('lang'));
    }

    //In Laravel 5.6, Blade will double encode special characters by default. If you would like to maintain the previous behavior of preventing double encoding, you may add Blade::withoutDoubleEncoding() to your AppServiceProvider boot method.
    Blade::withoutDoubleEncoding();

    //Laravel 5.6 uses Bootstrap 4 by default. Shift did not update your front-end resources or dependencies as this could impact your UI. If you are using Bootstrap and wish to continue using Bootstrap 3, you should add Paginator::useBootstrapThree() to your AppServiceProvider boot method.
    Paginator::useBootstrapThree();

    /*
    |--------------------------------------------------------------------------
    | My Health public view namespace
    |--------------------------------------------------------------------------
    |
    | The public login page can be loaded before the MyHealthMembers module
    | service provider is booted by the module package. Registering the view
    | namespace here prevents the login/register popup from failing with:
    | "No hint path defined for [myhealthmembers]".
    |
    */
    if (is_dir(base_path('Modules/MyHealthMembers/Resources/views'))) {
      View::addNamespace('myhealthmembers', base_path('Modules/MyHealthMembers/Resources/views'));
    }


    /*
    |--------------------------------------------------------------------------
    | Auto Service module fallback loader
    |--------------------------------------------------------------------------
    | Some installations of this ERP do not automatically boot newly added
    | module service providers until the module registry/cache is refreshed.
    | Loading the AutoService view namespace and routes here makes the module
    | reachable without touching other working modules.
    */
    if (is_dir(base_path('Modules/AutoService/Resources/views'))) {
      View::addNamespace('autoservice', base_path('Modules/AutoService/Resources/views'));
    }

    // AutoService routes load through the module provider only.


    /*
    |--------------------------------------------------------------------------
    | Standalone SalesAgent Module Routes
    |--------------------------------------------------------------------------
    |
    | The SalesAgent route file is valid and registers routes when manually
    | required. Loading it here guarantees it is registered during application
    | boot without placing route definitions in the main routes/web.php file.
    |
    */
    // SalesAgent routes load through the module provider only.


    /*
    |--------------------------------------------------------------------------
    | Chequer module fallback loader
    |--------------------------------------------------------------------------
    | Chequer is a standalone module and all route definitions remain inside
    | Modules/Chequer/Routes/web.php. This small bootstrap bridge only ensures
    | the module route file is loaded on installations where newly added module
    | service providers are not picked up until the module cache is refreshed.
    */
    if (is_dir(base_path('Modules/Chequer/Resources/views'))) {
      View::addNamespace('chequer', base_path('Modules/Chequer/Resources/views'));
    }

    // Chequer routes load through the module provider only.


    /*
    |--------------------------------------------------------------------------
    | Stock Taking - New view fallback
    |--------------------------------------------------------------------------
    | The provider is conditionally registered in register(). Keep the view
    | namespace available here as an additional safeguard for transferred
    | installations whose provider manifest was generated before this module.
    */
    $stockTakingNewViews = base_path('Modules/StockTakingNew/Resources/views');
    if (is_dir($stockTakingNewViews)) {
      View::addNamespace('stocktakingnew', $stockTakingNewViews);
    }


    /*
    |--------------------------------------------------------------------------
    | Products New module fallback loader
    |--------------------------------------------------------------------------
    |
    | Newly uploaded modules are not always discovered immediately by the host
    | module registry. Keep all Products New route definitions inside the module
    | and use this small bridge only to guarantee registration on tenant sites.
    | The shared guard prevents duplicate routes if the module provider is active.
    |
    */
    $productsNewViews = base_path('Modules/ProductsNew/Resources/views');
    if (is_dir($productsNewViews)) {
      View::addNamespace('productsnew', $productsNewViews);
    }

    $productsNewProviderLoaded = (bool) (
      app()->getLoadedProviders()[\Modules\ProductsNew\Providers\ProductsNewServiceProvider::class] ?? false
    );

    if (! $productsNewProviderLoaded
      && !app()->routesAreCached()
      && !app()->bound('productsnew.routes_loaded')
      && !\Illuminate\Support\Facades\Route::has('products-new.dashboard')) {
      app()->instance('productsnew.routes_loaded', true);

      foreach (['web.php', 'reports.php'] as $productsNewRouteFile) {
        $productsNewRoutePath = base_path('Modules/ProductsNew/Routes/' . $productsNewRouteFile);
        if (file_exists($productsNewRoutePath)) {
          require $productsNewRoutePath;
        }
      }

      $productsNewApiRoutes = base_path('Modules/ProductsNew/Routes/api.php');
      if (file_exists($productsNewApiRoutes)) {
        \Illuminate\Support\Facades\Route::prefix('api')
          ->middleware('api')
          ->group($productsNewApiRoutes);
      }
    }



    $asset_v = config('constants.asset_version', 1);
    View::share('asset_v', $asset_v);
    // Resolve global view data once per request. A wildcard composer runs for
    // every rendered partial, so repeated subscription/permission queries here
    // previously multiplied the cost of each page.
    View::composer(['*'], function ($view) {
      static $resolved = false;
      static $shared = [];

      if (! $resolved) {
        $resolved = true;

        $enabledModules = session('business.enabled_modules', []);
        if (is_string($enabledModules)) {
          $decoded = json_decode($enabledModules, true);
          $enabledModules = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        $businessId = session('user.business_id');
        $businessId = $businessId !== null ? (int) $businessId : null;
        $enabledModules = SidebarPermissionUtil::filterEnabledModules((array) $enabledModules, $businessId);

        $canOfflineAccess = false;
        $canOfflineSyncManage = false;

        try {
          if (auth()->check() && $businessId) {
            $userId = (int) auth()->id();
            $offlineMode = false;

            if (class_exists(\Modules\Superadmin\Entities\Subscription::class)) {
              $offlineMode = (bool) GlobalPerformanceCache::remember(
                'offline_mode',
                [$businessId],
                60,
                static function () use ($businessId) {
                  $subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($businessId);
                  return ! empty($subscription) && ! empty($subscription->package_details['offline_mode']);
                }
              );
            }

            if ($offlineMode) {
              if (auth()->user()->can('business_settings.access')) {
                $canOfflineAccess = true;
                $canOfflineSyncManage = true;
              } else {
                $permissionFlags = GlobalProviderViewCache::remember(
                  'offline_permission_flags',
                  [$businessId, $userId],
                  60,
                  static function () use ($businessId, $userId): array {
                    $permission = UserStorePermission::query()
                      ->where('business_id', $businessId)
                      ->where('user_id', $userId)
                      ->first(['offline_access', 'offline_sync_manage']);

                    return [
                      'offline_access' => (bool) optional($permission)->offline_access,
                      'offline_sync_manage' => (bool) optional($permission)->offline_sync_manage,
                    ];
                  }
                );

                $canOfflineAccess = (bool) ($permissionFlags['offline_access'] ?? false);
                $canOfflineSyncManage = (bool) ($permissionFlags['offline_sync_manage'] ?? false);
              }
            }
          }
        } catch (\Throwable $e) {
          // Global view data is optional and must not break page rendering.
        }

        $shared = [
          'enabled_modules' => $enabledModules,
          'sidebar_enabled_modules' => $enabledModules,
          'can_offline_access' => $canOfflineAccess,
          'can_offline_sync_manage' => $canOfflineSyncManage,
        ];
      }

      $view->with($shared);
    });

    //This will fix "Specified key was too long; max key length is 767 bytes issue during migration"
    Schema::defaultStringLength(191);

    //Blade directive to format number into required format.
    Blade::directive('num_format', function ($expression) {
      return "number_format($expression,  session('business.currency_precision') ?? 2, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',')";
    });



    //Blade directive to format quantity values into required format.
    Blade::directive('format_quantity', function ($expression) {
      return "number_format($expression, session('business.quantity_precision') ?? 2, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',')";
    });

    //Blade directive to return appropiate class according to transaction status
    Blade::directive('transaction_status', function ($status) {
      return "<?php if($status == 'ordered'){
                echo 'bg-aqua';
            }elseif($status == 'pending'){
                echo 'bg-red';
            }elseif ($status == 'received') {
                echo 'bg-light-green';
            }?>";
});

//Blade directive to return appropiate class according to transaction status
Blade::directive('payment_status', function ($status) {
return "<?php if($status == 'partial'){
                echo 'bg-aqua';
            }elseif($status == 'due'){
                echo 'bg-yellow';
            }elseif ($status == 'paid') {
                echo 'bg-light-green';
            }elseif ($status == 'overdue') {
                echo 'bg-red';
            }elseif ($status == 'partial-overdue') {
                echo 'bg-red';
            }elseif ($status == 'pending') {
                echo 'bg-info';
            }elseif ($status == 'over-payment') {
                echo 'bg-light-green';
            }elseif ($status == 'price-later') {
                echo 'bg-orange';
            }?>";
});

//Blade directive to return appropiate class according to order status
Blade::directive('order_status', function ($status) {
return "<?php if($status == 'ordered'){
                echo 'bg-aqua';
            }elseif($status == 'waiting_for_confirmation'){
                echo 'bg-yellow';
            }elseif ($status == 'confirmed') {
                echo 'bg-light-green';
            }elseif ($status == 'invoiced') {
                echo 'bg-danger';
            }elseif ($status == 'shipped') {
                echo 'bg-red';
            }elseif ($status == 'delivered') {
                echo 'bg-info';
            }?>";
});

//Blade directive to display help text.
Blade::directive('show_tooltip', function ($message) {
return "<?php
                if(session('business.enable_tooltip')){
                    echo '<i class=\"fa fa-info-circle text-info hover-q no-print \" aria-hidden=\"true\" 
                    data-container=\"body\" data-toggle=\"popover\" data-placement=\"auto bottom\" 
                    data-content=\"' . $message . '\" data-html=\"true\" data-trigger=\"hover\"></i>';
                }
                ?>";
});

//Blade directive to convert.
Blade::directive('format_date', function ($date) {
if (!empty($date)) {
return "\Carbon::createFromTimestamp(strtotime($date))->format(session('business.date_format'))";
} else {
return null;
}
});

//Blade directive to convert.
Blade::directive('format_time', function ($date) {
if (!empty($date)) {
$time_format = 'h:i A';
if (session('business.time_format') == 24) {
$time_format = 'H:i';
}
return "\Carbon::createFromTimestamp(strtotime($date))->format('$time_format')";
} else {
return null;
}
});

/*
 |------------------------------------------------------------------------------
 | @format_datetime - the time format must be resolved at RUNTIME.
 |------------------------------------------------------------------------------
 |
 | This read session('business.time_format') inside the directive closure, which
 | runs when the view is COMPILED, and baked the result into the cached compiled
 | file:
 |
 |     $time_format = 'h:i A';
 |     if (session('business.time_format') == 24) { $time_format = 'H:i'; }
 |     return "...->format(session('business.date_format') . ' ' . '$time_format')";
 |
 | The date format was evaluated at runtime; the TIME format was not. So whichever
 | business happened to trigger the compile fixed the time format for every
 | business afterwards, until the view cache was cleared. A 24-hour business could
 | be served 'h:i A' and a 12-hour business 'H:i'.
 |
 | That also breaks the date pickers: the browser parses these values with
 | moment_time_format, derived at runtime from the SAME setting. When the two
 | disagree the parse fails, and a picker seeded from the field can land on a
 | date that has nothing to do with the value the server rendered.
 |
 | Both formats are now resolved when the view RENDERS.
 */
Blade::directive('format_datetime', function ($date) {
    if (empty($date)) {
        return null;
    }

    /*
     * A BARE EXPRESSION, not <?php echo ... ?>.
     *
     * 127 of the call sites sit inside {{ }} or {!! !!} - for example
     * Form::text('operation_date', @format_datetime('now'), ...) - where the
     * compiled output must be a PHP sub-expression. An echo tag there would
     * produce broken PHP across roughly 200 views.
     */
    return "\\Carbon::createFromTimestamp(strtotime($date))->format("
        . "session('business.date_format') . ' ' . (session('business.time_format') == 24 ? 'H:i' : 'h:i A')"
        . ")";
});

//Blade directive to format currency.
Blade::directive('format_currency', function ($number) {
return '<?php
            $sessionCurrency = session("currency", []);
            $sessionBusiness = session("business");
            $currencyCode = $sessionCurrency["code"] ?? session("business_currency_code", "");
            $currencySymbol = $sessionCurrency["symbol"] ?? session("business_currency_symbol", "");
            if ((empty($currencySymbol) || trim((string) $currencySymbol) === "$") && strtoupper((string) $currencyCode) !== "USD") {
                $currencySymbol = $currencyCode;
            }
            $currencyPlacement = data_get($sessionBusiness, "currency_symbol_placement")
                ?? session("business_currency_symbol_placement", "before");
            $currencyPrecision = data_get($sessionBusiness, "currency_precision")
                ?? session("business.currency_precision")
                ?? config("constants.currency_precision", 2);
            $formated_number = "";
            if ($currencyPlacement == "before") {
                $formated_number .= trim((string) $currencySymbol) . " ";
            }
            $formated_number .= number_format((float) ' . $number . ', (int) $currencyPrecision, $sessionCurrency["decimal_separator"] ?? ".", $sessionCurrency["thousand_separator"] ?? ",");
            if ($currencyPlacement == "after") {
                $formated_number .= " " . trim((string) $currencySymbol);
            }
            echo trim($formated_number); ?>';
});

Event::listen(TenancyBootstrapped::class, function (TenancyBootstrapped $event) {
\Spatie\Permission\PermissionRegistrar::$cacheKey = 'spatie.permission.cache.tenant.' . $event->tenancy->tenant->id;

// Tenancy may replace URL generator defaults after service providers boot.
// Re-apply the actual browser host after tenant initialization so action(),
// route(), url(), form actions and redirects remain on the tenant domain.
if (! app()->runningInConsole()) {
  $request = request();
  $forwardedProto = (string) $request->headers->get('X-Forwarded-Proto', '');
  $scheme = $forwardedProto !== ''
    ? strtolower(trim(explode(',', $forwardedProto)[0]))
    : $request->getScheme();

  if (! in_array($scheme, ['http', 'https'], true)) {
    $scheme = $request->isSecure() ? 'https' : 'http';
  }

  $host = $request->getHttpHost();
  if ($host !== '') {
    \Illuminate\Support\Facades\URL::forceRootUrl($scheme . '://' . $host);
    \Illuminate\Support\Facades\URL::forceScheme($scheme);
  }
}
});

$this->registerCommands();

if (! app()->runningInConsole()) {
  $subdomain = explode('.', $request->getHost())[0] ?? null;

  if ($subdomain) {
    $date = date('Y-m-d');
    $logPath = storage_path("logs/tenants/{$subdomain}/laravel-{$date}.log");
    $logger = Log::channel('tenant')->getLogger();
    $logger->setHandlers([new StreamHandler($logPath)]);
  }

  // Branding is needed only for full HTML responses. DataTables/Ajax requests
  // must not pay for tenant metadata and legacy settings/config lookups.
  if (! $request->ajax() && ! $request->expectsJson()) {
    config([
      'feedback.settings.favicon' => link_settings('favicon'),
      'feedback.config.login_image' => link_config('secondary_image'),
      'feedback.config.site_name' => link_config('site_name'),
    ]);
  }
}


// \App\Models\CashSettlement::migrate();
// \App\Models\DailyCashStatus::migrate();
}

/**
* Register any application services.
*
* @return void
*/
public function register()
{
  /*
  |------------------------------------------------------------------------
  | Stock Taking - New bootstrap bridge
  |------------------------------------------------------------------------
  | New module folders can be uploaded while the host still has an older
  | nwidart provider manifest. AppServiceProvider is already guaranteed to
  | load, so conditionally registering the module provider here prevents a
  | false 404 without moving any module routes or business logic outside the
  | standalone module. Laravel ignores duplicate provider registration.
  */
  $stockTakingProvider = \Modules\StockTakingNew\Providers\StockTakingNewServiceProvider::class;
  $stockTakingProviderFile = base_path('Modules/StockTakingNew/Providers/StockTakingNewServiceProvider.php');

  if (is_file($stockTakingProviderFile)
    && class_exists($stockTakingProvider)
    && ! $this->app->getProvider($stockTakingProvider)) {
    $this->app->register($stockTakingProvider);
  }

  /*
  |------------------------------------------------------------------------
  | Management Report bootstrap bridge
  |------------------------------------------------------------------------
  | This application can retain an older Nwidart module discovery manifest
  | after a module folder is uploaded. In that state `module:enable` updates
  | modules_statuses.json, but the module service provider is never booted and
  | its routes remain absent from `route:list`. Register only the standalone
  | ManagementReport provider here when the module is enabled. The provider
  | keeps its routes, views, permissions and business logic inside the module.
  */
  $managementReportEnabled = false;
  $moduleStatusFile = base_path('modules_statuses.json');

  if (is_file($moduleStatusFile)) {
    $moduleStatuses = json_decode((string) file_get_contents($moduleStatusFile), true);
    $managementReportEnabled = is_array($moduleStatuses)
      && ! empty($moduleStatuses['ManagementReport']);
  }

  $managementReportProvider = \Modules\ManagementReport\Providers\ManagementReportServiceProvider::class;
  $managementReportProviderFile = base_path(
    'Modules/ManagementReport/Providers/ManagementReportServiceProvider.php'
  );

  if ($managementReportEnabled && is_file($managementReportProviderFile)) {
    if (! class_exists($managementReportProvider, false)) {
      require_once $managementReportProviderFile;
    }

    if (class_exists($managementReportProvider, false)
      && ! $this->app->getProvider($managementReportProvider)) {
      $this->app->register($managementReportProvider);
    }
  }

  /*
  |------------------------------------------------------------------------
  | Simple Audit standalone bootstrap bridge
  |------------------------------------------------------------------------
  | The host can keep an older Nwidart module provider manifest after a new
  | standalone module is uploaded. In that state `module:enable SimpleAudit`
  | correctly updates modules_statuses.json, but the provider is not booted and
  | no simple-audit routes appear in `route:list`. Register only the module's
  | own provider when its global module status is explicitly enabled. All
  | routes, views, services and business logic remain inside Modules/SimpleAudit.
  */
  $simpleAuditEnabled = false;
  if (is_file($moduleStatusFile)) {
    $moduleStatuses = isset($moduleStatuses) && is_array($moduleStatuses)
      ? $moduleStatuses
      : json_decode((string) file_get_contents($moduleStatusFile), true);
    $simpleAuditEnabled = is_array($moduleStatuses)
      && ! empty($moduleStatuses['SimpleAudit']);
  }

  $simpleAuditProvider = \Modules\SimpleAudit\Providers\SimpleAuditServiceProvider::class;
  $simpleAuditProviderFile = base_path(
    'Modules/SimpleAudit/Providers/SimpleAuditServiceProvider.php'
  );

  if ($simpleAuditEnabled && is_file($simpleAuditProviderFile)) {
    if (! class_exists($simpleAuditProvider, false)) {
      require_once $simpleAuditProviderFile;
    }

    if (class_exists($simpleAuditProvider, false)
      && ! $this->app->getProvider($simpleAuditProvider)) {
      $this->app->register($simpleAuditProvider);
    }
  }


  /*
  |------------------------------------------------------------------------
  | Finance List Accounts standalone route bootstrap bridge
  |------------------------------------------------------------------------
  | Finance > List Accounts has been retired from the legacy
  | /accounting-module/account entry.  Some installations do not boot the
  | Finance/tenancy route providers during Artisan route discovery, so the
  | canonical /finance/account route can disappear completely.
  |
  | AppServiceProvider is guaranteed to load and already serves as the safe
  | bootstrap bridge for other standalone modules in this application.
  | Register one small dedicated provider here; all controller/view/business
  | logic remains inside Modules/Finance.
  */
  $financeAccountRouteProvider = \App\Providers\FinanceStandaloneAccountRouteServiceProvider::class;
  $financeAccountRouteProviderFile = base_path(
    'app/Providers/FinanceStandaloneAccountRouteServiceProvider.php'
  );

  if (is_file($financeAccountRouteProviderFile)) {
    if (! class_exists($financeAccountRouteProvider, false)) {
      require_once $financeAccountRouteProviderFile;
    }

    if (class_exists($financeAccountRouteProvider, false)
      && ! $this->app->getProvider($financeAccountRouteProvider)) {
      $this->app->register($financeAccountRouteProvider);
    }
  }
}

/**
* Register commands.
*
* @return void
*/
protected function registerCommands() {}
}