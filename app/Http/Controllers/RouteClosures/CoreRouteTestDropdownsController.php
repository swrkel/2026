<?php

namespace App\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;


/**
 * MA-002 - route closures moved out of routes/test_dropdowns.php.
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
class CoreRouteTestDropdownsController
{
    public function handle1()
    {
    $filter_dropdowns = \App\Agent::getFilterDropdowns();
    extract($filter_dropdowns);
    return view('superadmin::agents.partials.list_agents_tab', compact(
        'countries', 'agent_codes', 'agent_names', 'cities', 'mobile_numbers', 'usernames', 'nic_numbers', 'referral_codes', 'added_bys'
    ));
}
}
