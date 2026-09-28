<?php

namespace Modules\MyHealthMembers\Http\Controllers\Documents;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\MyHealthMembers\Entities\MyHealthDocument;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthDocumentController extends Controller
{
    public function index(MyHealthMember $member, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_view_documents'), 403);

        $documents = MyHealthDocument::where('member_id', $member->id)->latest()->paginate(25);
        $auditService->log($member->id, 'documents', 'view', 'Documents viewed.');

        return view('myhealthmembers::documents.index', compact('member', 'documents'));
    }

    public function store(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_upload_documents'), 403);

        $request->validate([
            'document_type' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $path = $request->file('file')->store('myhealth_documents/' . $member->id, 'public');

        MyHealthDocument::create([
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'uploaded_by' => auth()->id(),
            'document_type' => $request->input('document_type'),
            'title' => $request->input('title'),
            'file_path' => $path,
        ]);

        $auditService->log($member->id, 'documents', 'upload', 'Document uploaded.');

        return back()->with('status', __('myhealthmembers::lang.document_uploaded'));
    }

    public function download(MyHealthDocument $document, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_view_documents'), 403);

        $auditService->log($document->member_id, 'documents', 'download', 'Document downloaded.');

        return Storage::disk('public')->download($document->file_path);
    }
}
