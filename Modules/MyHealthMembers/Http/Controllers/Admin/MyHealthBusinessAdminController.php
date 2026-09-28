<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Services\Admin\MyHealthAdministrationService;

class MyHealthBusinessAdminController extends Controller
{
    public function index(MyHealthAdministrationService $service)
    {
        return view('myhealthmembers::admin.businesses.index', [
            'businesses' => $service->businesses(),
        ]);
    }

    public function show($business)
    {
        $record = DB::table('business')->where('id', $business)->first();
        abort_if(! $record, 404);

        return view('myhealthmembers::admin.businesses.show', ['business' => $record]);
    }
}
