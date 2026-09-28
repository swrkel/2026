<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Services\CustomerService;

class CustomerTimelineController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $customer = $this->customerService->customerQuery($businessId)->where('id', $id)->first();
        abort_if(empty($customer), 404);

        $items = collect([]);

        if (Schema::hasTable('customer_notes')) {
            $items = $items->merge(DB::table('customer_notes')->where('business_id', $businessId)->where('customer_id', $id)->get()->map(function ($row) {
                return ['type' => 'note', 'title' => ucfirst($row->note_type ?? 'general'), 'description' => $row->note, 'date' => $row->created_at];
            }));
        }

        if (Schema::hasTable('customer_activity_logs')) {
            $items = $items->merge(DB::table('customer_activity_logs')->where('business_id', $businessId)->where('customer_id', $id)->get()->map(function ($row) {
                return ['type' => 'activity', 'title' => ucwords(str_replace('_', ' ', $row->action ?? 'activity')), 'description' => $row->description, 'date' => $row->created_at];
            }));
        }

        if (Schema::hasTable('customer_attachments')) {
            $items = $items->merge(DB::table('customer_attachments')->where('business_id', $businessId)->where('customer_id', $id)->get()->map(function ($row) {
                return ['type' => 'attachment', 'title' => $row->title ?: $row->file_name, 'description' => $row->file_name, 'date' => $row->created_at];
            }));
        }

        if (Schema::hasTable('customer_documents')) {
            $items = $items->merge(DB::table('customer_documents')->where('business_id', $businessId)->where('customer_id', $id)->get()->map(function ($row) {
                return ['type' => 'document', 'title' => $row->title, 'description' => $row->file_name, 'date' => $row->created_at];
            }));
        }

        if (Schema::hasTable('customer_audit_trails')) {
            $items = $items->merge(DB::table('customer_audit_trails')->where('business_id', $businessId)->where('customer_id', $id)->get()->map(function ($row) {
                return ['type' => 'audit', 'title' => ucwords(str_replace('_', ' ', $row->action ?? 'audit')), 'description' => $row->description, 'date' => $row->created_at];
            }));
        }

        $items = $items->sortByDesc('date')->values();

        return view('customers::timeline.index')->with(compact('customer', 'items'));
    }
}
