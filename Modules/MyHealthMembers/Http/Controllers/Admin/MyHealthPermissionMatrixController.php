<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Admin\MyHealthPermissionMatrixService;

class MyHealthPermissionMatrixController extends Controller
{
    public function index(MyHealthPermissionMatrixService $service)
    {
        return view('myhealthmembers::admin.permissions.index', [
            'businesses' => $service->businesses(),
            'sections' => $service->sections(),
        ]);
    }

    public function store(Request $request, MyHealthPermissionMatrixService $service)
    {
        $service->save($request->all());
        return redirect()->route('myhealth.admin.permissions.index')->with('status', 'My Health permission matrix updated successfully.');
    }
}
