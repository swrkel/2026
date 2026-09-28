<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\PackageService;

class PackageController extends Controller
{
    public function index(PackageService $service) { return view('beautysaloons::packages.index', $service->indexData()); }
    public function create() { return view('beautysaloons::packages.create'); }
    public function store(Request $request, PackageService $service) { $service->store($request->all()); return redirect()->back()->with('status', __('beautysaloons::bs.package_saved')); }
    public function edit($id, PackageService $service) { return view('beautysaloons::packages.edit', $service->editData($id)); }
    public function update(Request $request, $id, PackageService $service) { $service->update($id, $request->all()); return redirect()->back()->with('status', __('beautysaloons::bs.package_updated')); }
    public function report(PackageService $service) { return view('beautysaloons::packages.report', $service->reportData()); }
}
