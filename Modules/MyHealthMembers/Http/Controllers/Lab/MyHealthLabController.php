<?php

namespace Modules\MyHealthMembers\Http\Controllers\Lab;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\MyHealthMembers\Entities\MyHealthLabRequest;
use Modules\MyHealthMembers\Entities\MyHealthLabResult;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthLabNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthLabController extends Controller
{
    public function index(MyHealthMember $member, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_view_medical_history'), 403);

        $labRequests = MyHealthLabRequest::where('member_id', $member->id)
            ->with('result')
            ->latest()
            ->paginate(25);

        $auditService->log($member->id, 'lab', 'view', 'Lab records viewed.');

        return view('myhealthmembers::lab.index', compact('member', 'labRequests'));
    }

    public function storeRequest(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthLabNumberService $numberService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $request->validate([
            'test_name' => ['required', 'string', 'max:255'],
            'request_date' => ['required', 'date'],
            'clinical_notes' => ['nullable', 'string'],
        ]);

        $labRequest = MyHealthLabRequest::create([
            'lab_request_no' => $numberService->nextLabRequestNo(),
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'requested_by' => auth()->id(),
            'request_date' => $request->input('request_date'),
            'test_name' => $request->input('test_name'),
            'clinical_notes' => $request->input('clinical_notes'),
            'status' => 'pending',
        ]);

        $auditService->log($member->id, 'lab', 'create_request', 'Lab request created: ' . $labRequest->lab_request_no);

        return back()->with('status', __('myhealthmembers::lang.lab_request_saved'));
    }

    public function storeResult(MyHealthLabRequest $labRequest, Request $request, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_upload_documents'), 403);

        $request->validate([
            'result_date' => ['required', 'date'],
            'result_summary' => ['nullable', 'string'],
            'result_details' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('myhealth_lab_results/' . $labRequest->member_id, 'public');
        }

        MyHealthLabResult::updateOrCreate(
            ['lab_request_id' => $labRequest->id],
            [
                'member_id' => $labRequest->member_id,
                'business_id' => $permissionService->businessId(),
                'resulted_by' => auth()->id(),
                'result_date' => $request->input('result_date'),
                'result_summary' => $request->input('result_summary'),
                'result_details' => $request->input('result_details'),
                'file_path' => $path,
            ]
        );

        $labRequest->update(['status' => 'resulted']);

        $auditService->log($labRequest->member_id, 'lab', 'result', 'Lab result saved: ' . $labRequest->lab_request_no);

        return back()->with('status', __('myhealthmembers::lang.lab_result_saved'));
    }
}
