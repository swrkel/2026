<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewAuditLog;

class MembershipNewAuditController extends Controller
{
    public function index(Request $request)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        $records = MembershipNewAuditLog::forBusiness($businessId)
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%' . $request->action . '%'))
            ->when($request->filled('entity_type'), fn ($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%' . $request->q . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('action', 'like', $like)->orWhere('entity_type', 'like', $like)->orWhere('ip_address', 'like', $like);
                });
            })
            ->latest()
            ->paginate(in_array((int) $request->input('per_page', 50), [10,25,50,100,200], true) ? (int) $request->input('per_page', 50) : 50)
            ->appends($request->query());

        return view('membershipnew::audit.index', compact('records'));
    }
}
