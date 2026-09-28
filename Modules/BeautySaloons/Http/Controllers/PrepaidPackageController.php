<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\PrepaidPackageService;

class PrepaidPackageController extends Controller
{
    public function index(PrepaidPackageService $service)
    {
        return view('beautysaloons::prepaid_packages.index', $service->indexData());
    }

    public function create()
    {
        return view('beautysaloons::prepaid_packages.create');
    }

    public function store(Request $request, PrepaidPackageService $service)
    {
        $service->store($request->all());
        return redirect()->route('beauty-saloons.prepaid-packages.index')->with('status', 'Prepaid package saved successfully');
    }

    public function sell(Request $request, PrepaidPackageService $service)
    {
        $service->sell($request->all());
        return redirect()->back()->with('status', 'Prepaid package sale saved successfully');
    }

    public function consume(Request $request, $saleId, PrepaidPackageService $service)
    {
        $service->consume((int) $saleId, $request->all());
        return redirect()->back()->with('status', 'Prepaid package usage saved successfully');
    }

    public function report(PrepaidPackageService $service)
    {
        return view('beautysaloons::prepaid_packages.report', $service->reportData());
    }
}
