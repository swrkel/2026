<?php

use Carbon\Carbon;
use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * boots pos.
 */
function pos_boot($ul, $pt, $lc, $em, $un, $type = 1, $pid = null)
{

  $ch = curl_init();
  $request_url = ($type == 1) ? base64_decode(config('author.lic1')) : base64_decode(config('author.lic2'));

  $pid = is_null($pid) ? config('author.pid') : $pid;

  $curlConfig = [
    CURLOPT_URL => $request_url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POSTFIELDS     => [
      'url' => $ul,
      'path' => $pt,
      'license_code' => $lc,
      'email' => $em,
      'username' => $un,
      'product_id' => $pid
    ]
  ];
  curl_setopt_array($ch, $curlConfig);
  $result = curl_exec($ch);

  if (curl_errno($ch)) {
    $error_msg = 'C' . 'U' . 'RL ' . 'E' . 'rro' . 'r: ';
    $error_msg .= curl_errno($ch);

    return redirect()->back()
      ->with('error', $error_msg);
  }
  curl_close($ch);

  if ($result) {
    $result = json_decode($result, true);

    if ($result['flag'] == 'valid') {
      // if(!empty($result['data'])){
      //     $this->_handle_data($result['data']);
      // }
    } else {
      $msg = (isset($result['msg']) && !empty($result['msg'])) ? $result['msg'] : "I" . "nvali" . "d " . "Lic" . "ense Det" . "ails";
      return redirect()->back()
        ->with('error', $msg);
    }
  }
}

if (! function_exists('humanFilesize')) {
  function humanFilesize($size, $precision = 2)
  {
    $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $step = 1024;
    $i = 0;

    while (($size / $step) > 0.9) {
      $size = $size / $step;
      $i++;
    }

    return round($size, $precision) . $units[$i];
  }
}

/**
 * Checks if the uploaded document is an image
 */
if (! function_exists('isFileImage')) {
  function isFileImage($filename)
  {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $array = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'GIF'];
    $output = in_array($ext, $array) ? true : false;

    return $output;
  }
}

/**
 * Checks number formate and round
 */
if (! function_exists('numberFormate')) {
  function numberFormate($number)
  {
    return number_format(round($number, 2));
  }
}


if (! function_exists('hasSubscriptionAccess')) {
  function hasSubscriptionAccess($moduleKey)
  {
    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::current_subscription($business_id);

    if (!empty($subscription)) {
      $pacakge_details = $subscription->package_details;
      $disable_all_other_module_vr = 0;

      if (array_key_exists('disable_all_other_module_vr', $pacakge_details)) {
        $disable_all_other_module_vr = $pacakge_details['disable_all_other_module_vr'];
      }

      if ($disable_all_other_module_vr == 0) {
        return array_key_exists($moduleKey, $pacakge_details);
      }
    }
    return false;
  }
}

if (! function_exists('defaultCustomerForm')) {
  function defaultCustomerForm()
  {
    return [
      'customer_name' => 1,
      'need_to_send_sms' => 1,
      'credit_notification_type' => 1,
      'vat_no' => 1,
      'customer_opening_balance' => 1,
      'customer_customer_group' => 1,
      'customer_credit_limit' => 1,
      'customer_transaction_date' => 1,
      'add_more_mobile' => 1,
      'customer_mobile' => 1,
      'customer_landline' => 1,
      'assigned_to' => 1,
      'customer_address' => 1,
      'address_line_2' => 1,
      'customer_city' => 1,
      'customer_state' => 1,
      'customer_country' => 1,
      'customer_landmark' => 1,
    ];
  }
}



if (!function_exists('displayPhoneFormatForApi')) {
  function displayPhoneFormatForApi($str)
  {

    if ($str != '') {
      $str = cleanPhoneNumber($str);
      $str =  substr($str, 0, 3) . ' ' . substr($str, 3, 3) . ' ' . substr($str, 6, 5);
    }
    return $str;
  }
}
if (!function_exists('cleanPhoneNumber')) {
  function cleanPhoneNumber($str, $isBird = false)
  {
    $phone = str_replace(array('(', ')', '.', '-', ' ', '+', '_'), array('', '', '', '', '', ''), $str);;
    if ($isBird)
      return $phone;
    $str = substr($phone, -10);
    return $str;
  }
}
if (!function_exists('displayPhoneFormat')) {
  function displayPhoneFormat($str)
  {

    if ($str != '' && strlen($str) > 3) {
      $str = cleanPhoneNumber($str);
      $str = '(' . substr($str, 0, 3) . ') ' . substr($str, 3, 3) . '-' . substr($str, 6, 5);
    } else {
      $str = '(xxx)xxx-xxxx';
    }
    return $str;
  }
}

if (!function_exists('locationCurrency')) {
  function locationCurrency($number, $currency, $symbol_left_side = true)
  {

    return ($symbol_left_side) ? $currency->symbol . number_format($number) : number_format($number) . $currency->symbol;
  }
}

if (!function_exists('createDate')) {
  function createDate($data, $formate = 'Y-m-d')
  {

    $years = implode($data['y']);
    $month = implode($data['m']);
    $day = implode($data['d']);
    if ($years == ''  || $month == '' ||  $day == '') {
      return null;
    }
    return Carbon::createFromFormat($formate, $years . '-' . $month . '-' . $day)->format($formate);
  }
}
if (!function_exists('createDateArray')) {
  function createDateArray($date)
  {
    return [
      'y' => str_split(Carbon::parse($date)->format('Y')),
      'm' => str_split(Carbon::parse($date)->format('m')),
      'd' => str_split(Carbon::parse($date)->format('d')),
    ];
  }
}

if (!function_exists('bussionLocation')) {
  function bussionLocation()
  {
    $business_id = request()->session()->get('user.business_id');
    return BusinessLocation::whereBusinessId($business_id)->pluck('name', 'id');
  }
}



if (!function_exists('currencyFormat')) {
  function currencyFormat($value)
  {
    $value = floatval($value);

    if (((is_numeric($value) && floor($value) != $value) == true)) {

      return floatval($value);
    }
    if ($value == 0) return 0;
    $business_id = request()->session()->get('business.id');
    $currency_precision = \App\Business::where('id', $business_id)->value('currency_precision');

    return number_format($value, $currency_precision, '.', ',');
  }
}

if (!function_exists('quantityFormat')) {
  function quantityFormat($value, $category = null)
  {
    $value = floatval($value);

    if (((is_numeric($value) && floor($value) != $value) == true)) {

      return floatval($value);
    }
    if ($value == 0) return 0;
    if ($category != 'fuel') {
      $quantity_precision = 3;
    } else {
      $business_id = request()->session()->get('business.id');
      $quantity_precision = \App\Business::where('id', $business_id)->value('quantity_precision');
    }

    return number_format($value, $quantity_precision, '.', ',');
  }
}

if (!function_exists('getAllowedLocations')) {
  function getAllowedLocations()
  {
    return auth()->user()->allowed_locations ?? collect();
  }
}

if (!function_exists('getCurrentLocation')) {
  function getCurrentLocation()
  {
    return auth()->user()->current_location;
  }
}

if (!function_exists('getCurrentLocationId')) {
  function getCurrentLocationId()
  {
    return session()->get('user.current_location');
  }
}

if (!function_exists('canAccessLocation')) {
  function canAccessLocation($locationId)
  {
    return auth()->user()->canAccessLocation($locationId);
  }
}

if (!function_exists('switchLocation')) {
  function switchLocation($locationId)
  {
    if (canAccessLocation($locationId)) {
      session(['user.current_location' => $locationId]);
      return true;
    }

    return false;
  }
}


if (!function_exists('current_tenant')) {

  /**
   * Resolve the current tenant once per request.
   *
   * Prefer Stancl Tenancy's already initialized tenant. Only fall back to a
   * single central-domain join when the tenancy middleware has not initialized
   * yet (for example, early login-page rendering on a tenant domain).
   */
  function current_tenant()
  {
    static $resolved = false;
    static $tenant = null;

    if ($resolved) {
      return $tenant;
    }

    $resolved = true;

    if (app()->runningInConsole()) {
      return null;
    }

    try {
      if (function_exists('tenancy') && tenancy()->initialized) {
        $tenant = tenant();
        return $tenant;
      }
    } catch (\Throwable $e) {
      // Continue with the central-domain fallback below.
    }

    try {
      $host = request()->getHost();
      if (!$host) {
        return null;
      }

      $centralConnection = config('tenancy.database.central_connection', config('database.default', 'mysql'));

      $tenant = DB::connection($centralConnection)
        ->table('domains as d')
        ->join('tenants as t', 't.id', '=', 'd.tenant_id')
        ->where('d.domain', $host)
        ->select('t.*')
        ->first();
    } catch (\Throwable $e) {
      $tenant = null;
    }

    return $tenant;
  }
}


if (!function_exists('tenant_db')) {

  /**
   * Configure the legacy mysql_tenant connection once per request.
   *
   * Do not eagerly reconnect. Laravel opens the connection on the first real
   * query, avoiding repeated DNS/socket handshakes during application boot.
   */
  function tenant_db()
  {
    static $configuredDatabase = null;

    if (app()->runningInConsole()) {
      return null;
    }

    $tenant = current_tenant();
    if (!$tenant) {
      return null;
    }

    $data = is_array($tenant->data ?? null)
      ? $tenant->data
      : json_decode((string) ($tenant->data ?? ''), true);

    $dbName = $data['tenancy_db_name'] ?? null;
    if (!$dbName) {
      return $tenant;
    }

    if ($configuredDatabase !== $dbName) {
      $existingDatabase = config('database.connections.mysql_tenant.database');

      config([
        'database.connections.mysql_tenant' => array_merge(
          (array) config('database.connections.mysql_tenant', []),
          ['database' => $dbName]
        )
      ]);

      // Purge only when changing from a previously opened different database.
      if ($existingDatabase && $existingDatabase !== $dbName) {
        DB::purge('mysql_tenant');
      }

      $configuredDatabase = $dbName;
    }

    app()->instance('tenant', $tenant);

    return $tenant;
  }
}


if (!function_exists('link_settings')) {

  function link_settings(string $column, $default = null)
  {
    static $values = [];

    $cacheKey = $column . '|' . serialize($default);
    if (array_key_exists($cacheKey, $values)) {
      return $values[$cacheKey];
    }

    if (app()->runningInConsole()) {
      return $values[$cacheKey] = $default;
    }

    tenant_db();

    try {
      if (config('database.connections.mysql_tenant.database')) {
        $tenantValue = DB::connection('mysql_tenant')
          ->table('settings')
          ->value($column);

        if (!is_null($tenantValue) && $tenantValue !== '') {
          return $values[$cacheKey] = $tenantValue;
        }
      }
    } catch (\Throwable $e) {
      // Ignore missing legacy table/column and use the main fallback.
    }

    try {
      $mainValue = DB::connection('mysql')
        ->table('settings')
        ->value($column);

      if (!is_null($mainValue) && $mainValue !== '') {
        return $values[$cacheKey] = $mainValue;
      }
    } catch (\Throwable $e) {
      // Ignore main DB errors and return the supplied default.
    }

    return $values[$cacheKey] = $default;
  }
}

if (!function_exists('link_config')) {

  function link_config(string $key, $default = null)
  {
    static $values = [];

    $cacheKey = $key . '|' . serialize($default);
    if (array_key_exists($cacheKey, $values)) {
      return $values[$cacheKey];
    }

    if (app()->runningInConsole()) {
      return $values[$cacheKey] = $default;
    }

    tenant_db();

    try {
      if (config('database.connections.mysql_tenant.database')) {
        $tenantValue = DB::connection('mysql_tenant')
          ->table('config')
          ->where('config_key', $key)
          ->value('config_value');

        if (!is_null($tenantValue) && $tenantValue !== '') {
          return $values[$cacheKey] = $tenantValue;
        }
      }
    } catch (\Throwable $e) {
      // Ignore missing legacy table and use the main fallback.
    }

    try {
      $mainValue = DB::connection('mysql')
        ->table('config')
        ->where('config_key', $key)
        ->value('config_value');

      if (!is_null($mainValue) && $mainValue !== '') {
        return $values[$cacheKey] = $mainValue;
      }
    } catch (\Throwable $e) {
      // Ignore main DB errors and return the supplied default.
    }

    return $values[$cacheKey] = $default;
  }
}

if (!function_exists('tenant_database_size_mb')) {
  /**
   * Get the current business database size in MB.
   */
  function tenant_database_size_mb(): float
  {
    $tenant = current_tenant();
    tenant_db();

    $connection = $tenant && config('database.connections.mysql_tenant.database')
      ? 'mysql_tenant'
      : config('database.default');

    $databaseName = DB::connection($connection)->getDatabaseName()
      ?: config("database.connections.{$connection}.database");

    if (empty($databaseName)) {
      return 0.0;
    }

    try {
      $result = DB::connection($connection)->selectOne(
        'SELECT COALESCE(SUM(data_length + index_length), 0) AS total_bytes
         FROM information_schema.TABLES
         WHERE table_schema = ?',
        [$databaseName]
      );

      $totalBytes = (float)($result->total_bytes ?? 0);

      return round($totalBytes / 1024 / 1024, 2);
    } catch (\Throwable $e) {
      return 0.0;
    }
  }
}

if (!function_exists('business_disk_limit_mb')) {
  /**
   * Get the effective allowed disk size in MB for a business.
   * Prefers the central business override over the package limit. Tenant-local
   * values are only a fallback for older tenant copies.
   */
  function business_disk_limit_mb(int $businessId): float
  {
    try {
      $tenant = current_tenant();
      tenant_db();

      $businessIds = [$businessId];

      if (!empty($tenant)) {
        $tenantBusinessId = data_get(json_decode($tenant->data ?? '{}', true), 'business_id');

        if (empty($tenantBusinessId) && preg_match('/^(\d+)(?:[\-\.].*)?$/', (string) $tenant->id, $matches)) {
          $tenantBusinessId = (int) $matches[1];
        }

        if (!empty($tenantBusinessId)) {
          array_unshift($businessIds, (int) $tenantBusinessId);
        }
      }

      $businessIds = array_values(array_unique(array_filter(array_map('intval', $businessIds))));

      $centralConnection = config('database.connections.system.database')
        ? 'system'
        : config('tenancy.database.central_connection', 'mysql');

      if (Schema::connection($centralConnection)->hasTable('business')) {
        foreach ($businessIds as $id) {
          $centralBusiness = DB::connection($centralConnection)
            ->table('business')
            ->where('id', $id)
            ->first();

          $centralCommonSettings = !empty($centralBusiness->common_settings)
            ? json_decode($centralBusiness->common_settings, true)
            : [];

          $businessOverride = isset($centralCommonSettings['max_disk_size'])
            ? (float) $centralCommonSettings['max_disk_size']
            : 0;

          if ($businessOverride > 0) {
            return $businessOverride;
          }
        }
      }

      if (
        $tenant &&
        config('database.connections.mysql_tenant.database') &&
        Schema::connection('mysql_tenant')->hasTable('business')
      ) {
        foreach ($businessIds as $id) {
          $tenantBusiness = DB::connection('mysql_tenant')
            ->table('business')
            ->where('id', $id)
            ->first();

          $tenantCommonSettings = !empty($tenantBusiness->common_settings)
            ? json_decode($tenantBusiness->common_settings, true)
            : [];

          $tenantOverride = isset($tenantCommonSettings['max_disk_size'])
            ? (float) $tenantCommonSettings['max_disk_size']
            : 0;

          if ($tenantOverride > 0) {
            return $tenantOverride;
          }
        }
      }

      foreach ($businessIds as $id) {
        $subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($id);
        $packageDiskSize = (float) optional(optional($subscription)->package)->max_disk_size;

        if ($packageDiskSize > 0) {
          return $packageDiskSize;
        }
      }

      return 0.0;
    } catch (\Throwable $e) {
      return 0.0;
    }
  }
}

if (!function_exists('link_config')) {

  /**
   * Get config value (tenant → main fallback)
   *
   * @param string $key
   * @param mixed  $default
   * @return mixed
   */
  function link_config(string $key, $default = null)
  {
    // 🔹 Ensure tenant DB is connected (if tenant)
    tenant_db();

    try {
      if (
        config('database.connections.mysql_tenant.database') &&
        Schema::connection('mysql_tenant')->hasTable('config')
      ) {
        $tenantValue = DB::connection('mysql_tenant')
          ->table('config')
          ->where('config_key', $key)
          ->value('config_value');

        if (!is_null($tenantValue) && $tenantValue !== '') {
          return $tenantValue;
        }
      }
    } catch (\Throwable $e) {
      // silently ignore tenant DB issues
    }

    try {
      if (Schema::connection('mysql')->hasTable('config')) {
        $mainValue = DB::connection('mysql')
          ->table('config')
          ->where('config_key', $key)
          ->value('config_value');

        if (!is_null($mainValue) && $mainValue !== '') {
          return $mainValue;
        }
      }
    } catch (\Throwable $e) {
      // silently ignore main DB issues
    }

    return $default;
  }
}

/*
|--------------------------------------------------------------------------
| Tenant-safe URL helpers
|--------------------------------------------------------------------------
| Use these helpers for new tenant-facing code. Existing users do not need
| to configure anything; the current HTTP request host is detected
| automatically. Console commands safely fall back to Laravel defaults.
*/
if (! function_exists('tenant_url')) {
  function tenant_url($path = '', $parameters = [], $secure = null)
  {
    $relative = url($path, $parameters, $secure);

    if (app()->runningInConsole() || ! request()) {
      return $relative;
    }

    try {
      $parsedPath = parse_url($relative, PHP_URL_PATH) ?: '/';
      $query = parse_url($relative, PHP_URL_QUERY);
      $fragment = parse_url($relative, PHP_URL_FRAGMENT);
      $output = rtrim(request()->getSchemeAndHttpHost(), '/') . '/' . ltrim($parsedPath, '/');
      if ($query) { $output .= '?' . $query; }
      if ($fragment) { $output .= '#' . $fragment; }
      return $output;
    } catch (\Throwable $e) {
      return $relative;
    }
  }
}

if (! function_exists('tenant_route')) {
  function tenant_route($name, $parameters = [], $absolute = true)
  {
    $relative = route($name, $parameters, false);
    if (! $absolute) {
      return $relative;
    }
    return tenant_url($relative);
  }
}


if (! function_exists('num_format')) {
    /**
     * MA-002 (LA-1133) - num_format() existed ONLY as a Blade directive.
     *
     * app/Providers/AppServiceProvider.php registers
     *     Blade::directive('num_format', ...)
     * which is compiled into views. It is NOT a PHP function, so calling
     * num_format(...) from controller code is a fatal error:
     *
     *     Call to undefined function
     *     Modules\Petro\Http\Controllers\num_format()
     *
     * That is exactly what LA-1133 reported. Petro's and PetroPD's
     * AddPaymentController build a credit-sale table row as an HTML string in
     * PHP and call num_format() seven times each. The row was written to the
     * database first, which is why the entry appeared after a refresh even
     * though the response failed - the save succeeded and the HTML response
     * blew up afterwards.
     *
     * This defines the function with the SAME formatting the Blade directive
     * produces, so views and controllers agree:
     *     number_format($value,
     *         session('business.currency_precision') ?? 2,
     *         session('currency')['decimal_separator'] ?? '.',
     *         session('currency')['thousand_separator'] ?? ',')
     *
     * Guarded with function_exists so it cannot collide if a real num_format
     * is ever added elsewhere, and placed in app/Http/helpers.php which is
     * already listed in composer's autoload "files" - so no dump-autoload is
     * needed.
     *
     * @param  mixed  $value
     */
    function num_format($value)
    {
        $currency = session('currency');

        return number_format(
            (float) $value,
            session('business.currency_precision') ?? 2,
            is_array($currency) && isset($currency['decimal_separator']) ? $currency['decimal_separator'] : '.',
            is_array($currency) && isset($currency['thousand_separator']) ? $currency['thousand_separator'] : ','
        );
    }
}


/*
 * MA-002 - THE SAME TRAP AS num_format(), FOR THE DATE/TIME DIRECTIVES.
 *
 * format_date, format_datetime, format_time and format_quantity are all
 * registered ONLY as Blade directives in AppServiceProvider. They work
 * everywhere in a view and are a fatal error the moment they are called from
 * PHP - which is easy to do by accident, because the name gives no hint that
 * it is view-only.
 *
 * That is not hypothetical. Found while checking the LA-1133 fix:
 *
 *     Modules/RealTimeEntries/Http/Controllers/RealTimeEntriesController.php:115
 *         $label = $shift->name . ' (' . format_date($shift->shift_date)
 *                . ' to ' . (!empty($shift->closed_time)
 *                            ? format_datetime($shift->closed_time) : 'Open') . ')';
 *
 * That line is inside a mapWithKeys() closure whose result is returned as
 * JSON, so the shift dropdown it feeds would have failed with a 500 exactly
 * like the credit-sale Add button did.
 *
 * Each function below reproduces its directive's output precisely, including
 * the 12/24 hour switch on session('business.time_format'), so views and PHP
 * cannot drift apart. All are guarded with function_exists.
 * A NOTE ON WHICH Carbon CLASS
 * The Blade directives call \Carbon::createFromTimestamp(...). In this app
 * the root alias 'Carbon' is mapped in config/app.php to
 * App\Interfaces\Carbon - NOT to Carbon\Carbon, which is commented out on
 * the line above it. App\Interfaces\Carbon extends Carbon\Carbon and
 * currently overrides only parse(), so the two behave identically for the
 * methods used here. These functions nevertheless reference
 * App\Interfaces\Carbon explicitly, so that if that class ever overrides
 * createFromTimestamp or format, the directives and these functions cannot
 * drift apart.
 *
 */

if (! function_exists('format_date')) {
    function format_date($date)
    {
        if (empty($date)) {
            return null;
        }

        return \App\Interfaces\Carbon::createFromTimestamp(strtotime($date))
            ->format(session('business.date_format'));
    }
}

if (! function_exists('format_time')) {
    function format_time($date)
    {
        if (empty($date)) {
            return null;
        }

        $time_format = session('business.time_format') == 24 ? 'H:i' : 'h:i A';

        return \App\Interfaces\Carbon::createFromTimestamp(strtotime($date))->format($time_format);
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime($date)
    {
        if (empty($date)) {
            return null;
        }

        $time_format = session('business.time_format') == 24 ? 'H:i' : 'h:i A';

        return \App\Interfaces\Carbon::createFromTimestamp(strtotime($date))
            ->format(session('business.date_format') . ' ' . $time_format);
    }
}

if (! function_exists('format_quantity')) {
    function format_quantity($value)
    {
        $currency = session('currency');

        return number_format(
            (float) $value,
            session('business.quantity_precision') ?? 2,
            is_array($currency) && isset($currency['decimal_separator']) ? $currency['decimal_separator'] : '.',
            is_array($currency) && isset($currency['thousand_separator']) ? $currency['thousand_separator'] : ','
        );
    }
}

/*
| Load the settings helpers restored after the HelpGuide 8039 rewrite.
| require_once, so a module that also defines them cannot cause a redeclare.
*/
require_once __DIR__ . '/setting_helpers.php';
