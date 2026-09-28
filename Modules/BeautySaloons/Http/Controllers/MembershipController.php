<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\MembershipService;

class MembershipController extends Controller
{
    public function index(MembershipService $service) { return view('beautysaloons::memberships.index', $service->indexData()); }
    public function create() { return view('beautysaloons::memberships.create'); }
    public function store(Request $request, MembershipService $service) { $service->store($request->all()); return redirect()->back()->with('status', __('beautysaloons::bs.membership_saved')); }
    public function edit($id, MembershipService $service) { return view('beautysaloons::memberships.edit', $service->editData($id)); }
    public function update(Request $request, $id, MembershipService $service) { $service->update($id, $request->all()); return redirect()->back()->with('status', __('beautysaloons::bs.membership_updated')); }
    public function report(MembershipService $service) { return view('beautysaloons::memberships.report', $service->reportData()); }
}
