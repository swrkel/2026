<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Modules\Customers\Services\CustomerAuditService;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerService;

class CustomerDocumentController extends CustomerActionBaseController
{
    protected $auditService;

    public function __construct(CustomerService $customerService, CustomerPermissionService $permissionService, CustomerAuditService $auditService)
    {
        parent::__construct($customerService, $permissionService);
        $this->auditService = $auditService;
    }

    public function index($id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $businessId = (int) request()->session()->get('user.business_id');
        $attachments = $this->auditService->attachments($businessId, (int) $customer->id);
        $tableStatus = $this->auditService->tableStatus();

        return view('customers::documents.index', compact('customer', 'attachments', 'tableStatus'));
    }

    public function store(Request $request, $id)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);

        $request->validate([
            'document' => 'required|file|max:10240',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $businessId = (int) $request->session()->get('user.business_id');
        $attachment = $this->auditService->uploadAttachment(
            $businessId,
            (int) $customer->id,
            $request->file('document'),
            $request->input('remarks'),
            auth()->id()
        );

        $output = [
            'success' => ! empty($attachment),
            'msg' => ! empty($attachment) ? __('lang_v1.success') : 'Customer attachments table is not available. Please run CUS_SEP_003 SQL.',
        ];

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('customers.documents.index', $customer->id)->with('status', $output);
    }

    public function download(Request $request, $id, $attachmentId)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $businessId = (int) $request->session()->get('user.business_id');
        $attachment = $this->auditService->findAttachment($businessId, (int) $customer->id, (int) $attachmentId);

        if (! $attachment || ! File::exists(public_path($attachment->path))) {
            abort(404);
        }

        return response()->download(public_path($attachment->path), $attachment->original_filename ?: $attachment->filename);
    }

    public function destroy(Request $request, $id, $attachmentId)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);
        $businessId = (int) $request->session()->get('user.business_id');

        $deleted = $this->auditService->deleteAttachment($businessId, (int) $customer->id, (int) $attachmentId, auth()->id());

        $output = [
            'success' => $deleted,
            'msg' => $deleted ? __('lang_v1.success') : __('messages.something_went_wrong'),
        ];

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('customers.documents.index', $customer->id)->with('status', $output);
    }
}
