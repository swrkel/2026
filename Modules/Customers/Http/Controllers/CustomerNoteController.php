<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Services\CustomerActivityService;
use Modules\Customers\Services\CustomerService;

class CustomerNoteController extends Controller
{
    protected $customerService;
    protected $activityService;

    public function __construct(CustomerService $customerService, CustomerActivityService $activityService)
    {
        $this->customerService = $customerService;
        $this->activityService = $activityService;
    }

    public function store($customerId, Request $request)
    {
        abort_if(!Schema::hasTable('customer_notes'), 404, 'Customer notes table is not available yet.');

        $businessId = $request->session()->get('user.business_id');
        $userId = $request->session()->get('user.id');
        $customer = $this->customerService->customerQuery($businessId)->where('id', $customerId)->first();

        abort_if(empty($customer), 404);

        $request->validate([
            'note' => 'required|string|max:5000',
            'note_type' => 'nullable|string|max:50',
        ]);

        $branchColumn = $this->customerService->branchColumn();
        $locationId = $branchColumn ? data_get($customer, $branchColumn) : null;

        DB::table('customer_notes')->insert([
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'customer_id' => $customerId,
            'note_type' => $request->input('note_type', 'general'),
            'note' => $request->input('note'),
            'is_private' => $request->has('is_private') ? 1 : 0,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->activityService->log(
            $businessId,
            $customerId,
            'note_created',
            'Customer note added.',
            $userId,
            $locationId,
            [],
            ['note_type' => $request->input('note_type', 'general')]
        );

        return redirect()->route('customers.show', $customerId)->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.note_added_successfully')
        ]);
    }

    public function destroy($customerId, $noteId, Request $request)
    {
        abort_if(!Schema::hasTable('customer_notes'), 404);

        $businessId = $request->session()->get('user.business_id');
        $userId = $request->session()->get('user.id');
        $customer = $this->customerService->customerQuery($businessId)->where('id', $customerId)->first();

        abort_if(empty($customer), 404);

        DB::table('customer_notes')
            ->where('business_id', $businessId)
            ->where('customer_id', $customerId)
            ->where('id', $noteId)
            ->delete();

        $branchColumn = $this->customerService->branchColumn();
        $locationId = $branchColumn ? data_get($customer, $branchColumn) : null;

        $this->activityService->log($businessId, $customerId, 'note_deleted', 'Customer note deleted.', $userId, $locationId);

        return redirect()->route('customers.show', $customerId)->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.note_deleted_successfully')
        ]);
    }
}
