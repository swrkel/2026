<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Services\ApplicationService;

class ApplicationController extends Controller
{
    public function index()
    {
        $result = null;
        return view('leasing::applications.index', compact('result'));
    }

    public function calculate(Request $request, ApplicationService $service)
    {
        $result = $service->calculate(
            $request->get('gross_weight'),
            $request->get('net_weight'),
            $request->get('purity'),
            $request->get('market_rate'),
            $request->get('advance_percentage')
        );
        return view('leasing::applications.index', compact('result'));
    }
}
