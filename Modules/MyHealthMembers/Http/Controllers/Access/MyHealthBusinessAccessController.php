<?php

namespace Modules\MyHealthMembers\Http\Controllers\Access;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Access\MyHealthBusinessAccessService;

class MyHealthBusinessAccessController extends Controller
{
    protected MyHealthBusinessAccessService $service;

    public function __construct(MyHealthBusinessAccessService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['member_code', 'mobile', 'nic_no', 'status']);
        $requests = $this->service->listRequests($filters);

        return view('myhealthmembers::access.index', compact('requests', 'filters'));
    }

    public function create()
    {
        return view('myhealthmembers::access.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_code' => 'required|string|max:20',
            'purpose' => 'required|string|max:255',
            'access_sections' => 'nullable|array',
            'access_sections.*' => 'string|max:100',
        ]);

        $result = $this->service->requestAccess($data, $request->user());

        if ($request->ajax()) {
            return response()->json($result);
        }

        return redirect()
            ->route('myhealth.access.index')
            ->with('status', $result['message'] ?? 'Access request created.');
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'access_request_id' => 'required|integer',
            'otp' => 'required|string|max:10',
        ]);

        $result = $this->service->verifyOtp($data, $request->user());

        if ($request->ajax()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'status' : 'error', $result['message']);
    }

    public function revoke($id)
    {
        $this->service->revoke((int) $id);

        return back()->with('status', 'My Health access revoked successfully.');
    }
}
