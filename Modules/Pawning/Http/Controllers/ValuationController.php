<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Services\ValuationService;

class ValuationController extends Controller
{
    public function index()
    {
        $result = null;
        return view('pawning::valuations.index', compact('result'));
    }

    public function calculate(Request $request, ValuationService $service)
    {
        $result = $service->calculate(
            $request->get('gross_weight'),
            $request->get('net_weight'),
            $request->get('purity'),
            $request->get('market_rate'),
            $request->get('advance_percentage')
        );
        return view('pawning::valuations.index', compact('result'));
    }
}
