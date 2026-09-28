<?php

namespace App\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\StableLoginController;
use App\Http\Controllers\BusinessController;
use App\Services\DetectDatabaseChangesService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\CustomerCompatibilityController;
use Modules\DailyActivityReport\Http\Controllers\DailyActivityReportController;
use Modules\Discount\Http\Controllers\NewdiscountController;
use Modules\MPCS\Http\Controllers\Form9ASettingsController;
use Modules\MPCS\Http\Controllers\Form9CSettingsController;
use Modules\MyHealth\Http\Controllers\DoctorController;
use Modules\MyHealth\Http\Controllers\MedicationController;
use Modules\MyHealth\Http\Controllers\SugerReadingController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

/**
 * MA-002 - route closures moved out of routes/web.php.
 *
 * WHY: Laravel cannot run `php artisan route:cache` while ANY route is defined
 * with a closure. This installation has 8,613 routes across 349 files, and
 * without the cache every one is parsed and compiled on EVERY request,
 * including the login page. That is the multi-second delay.
 *
 * Only 35 closures across 14 files were blocking it.
 *
 * The method bodies are BYTE-IDENTICAL to the closures they replace. Nothing
 * was rewritten - the code simply lives in a class so the route can be cached.
 */
class CoreRouteWebController
{
    public function handle1()
    {
    Artisan::call('optimize:clear');
    return response()->json([
        'status'  => 'success',
        'message' => 'All cache cleared (config, route, view, event, cache).',
        'output'  => Artisan::output(),
    ]);
}

    public function handle2()
    {

    try {

        Artisan::call('cache:clear');

        Artisan::call('view:clear');

        Artisan::call('config:clear');

        Artisan::call('route:clear');

        $output = [

            'success' => true,

            'msg'     => __('lang_v1.success'),

        ];

    } catch (\Exception $e) {

        Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

        $output = [

            'success' => false,

            'msg'     => __('messages.something_went_wrong'),

        ];

    }

    return $output;

    // return what you want

}

    public function handle3()
    {

        return redirect('/index');

    }

    public function handle4()
    { return redirect()->route('customers.import'); }

    public function handle5()
    { return redirect()->route('customers.export'); }

    public function handle6()
    { return redirect()->route('customers.reports.balance'); }

    public function handle7()
    { return redirect('backup'); }

    public function handle8(DetectDatabaseChangesService $detectDatabaseChangesService)
    {
    $result = $detectDatabaseChangesService->runSqlUpdates();
    return response()->json($result);
}

    public function handle9($locale)
    {
    if (! array_key_exists($locale, config('app.languages', []))) {
        abort(404);
    }

    app()->setLocale($locale);

    // Collect translation groups
    $translations = [
        'lang_v1'  => __('lang_v1'),
        'report'   => __('report'),
        'messages' => __('messages'),
        'purchase' => __('purchase'),
        'account'  => __('account'),
        'hr'       => __('hr'),
        'petro' => __('petro'),
        'home'=> __('home'),
    ];

    // Recursively ensure all strings are UTF-8
    $utf8EncodeRecursive = function ($array) use (&$utf8EncodeRecursive) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $utf8EncodeRecursive($value);
            } else {
                $array[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }
        return $array;
    };

    $translations = $utf8EncodeRecursive($translations);

    return response()->json($translations);
}
}
