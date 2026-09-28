<?php

namespace Modules\PetroDirectNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroDirectNew\Services\DashboardService;
use Modules\PetroDirectNew\Services\SharedMasterDataService;
use Modules\PetroDirectNew\Support\PermissionGate;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard, private SharedMasterDataService $master) {}
    public function index(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.dashboard.view');
        $locationId=$request->integer('location_id') ?: null;
        return view('petrodirectnew::dashboard.index',[
            'summary'=>$this->dashboard->summary($locationId),'locations'=>$this->master->locations(),'locationId'=>$locationId,
        ]);
    }
}
