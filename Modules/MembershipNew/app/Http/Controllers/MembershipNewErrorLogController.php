<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewErrorLog;

class MembershipNewErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $records = MembershipNewErrorLog::forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('message', 'like', $search)->orWhere('error_class', 'like', $search);
                });
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%' . $request->q . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('message', 'like', $like)->orWhere('error_class', 'like', $like)->orWhere('file', 'like', $like);
                });
            })
            ->latest()
            ->paginate(in_array((int) $request->input('per_page', 50), [10,25,50,100,200], true) ? (int) $request->input('per_page', 50) : 50)
            ->appends($request->query());

        return view('membershipnew::error_logs.index', compact('records'));
    }
}
