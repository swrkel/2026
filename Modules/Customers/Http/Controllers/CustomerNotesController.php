<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerAuditService;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerService;

class CustomerNotesController extends CustomerActionBaseController
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
        $notes = $this->auditService->notes($businessId, (int) $customer->id);
        $tableStatus = $this->auditService->tableStatus();

        return view('customers::notes.index', compact('customer', 'notes', 'tableStatus'));
    }

    public function store(Request $request, $id)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);

        $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $businessId = (int) $request->session()->get('user.business_id');
        $note = $this->auditService->addNote($businessId, (int) $customer->id, trim($request->input('note')), auth()->id());

        $output = [
            'success' => ! empty($note),
            'msg' => ! empty($note) ? __('lang_v1.success') : 'Customer notes table is not available. Please run CUS_SEP_003 SQL.',
        ];

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('customers.notes.index', $customer->id)->with('status', $output);
    }

    public function destroy(Request $request, $id, $noteId)
    {
        $this->permissionService->authorize('edit');
        $customer = $this->getCustomer($id);
        $businessId = (int) $request->session()->get('user.business_id');

        $deleted = $this->auditService->deleteNote($businessId, (int) $customer->id, (int) $noteId, auth()->id());

        $output = [
            'success' => $deleted,
            'msg' => $deleted ? __('lang_v1.success') : __('messages.something_went_wrong'),
        ];

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('customers.notes.index', $customer->id)->with('status', $output);
    }
}
