<?php

namespace Modules\DistributionNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Mobile\DisnewMobileAuthService;

class DisnewMobileAuthController extends Controller
{
    public function login(Request $request, DisnewMobileAuthService $service)
    {
        return response()->json($service->login($request->all(), auth()->user()));
    }

    public function logout(Request $request, DisnewMobileAuthService $service)
    {
        return response()->json($service->logout($request->input('device_uuid'), auth()->id()));
    }
}
