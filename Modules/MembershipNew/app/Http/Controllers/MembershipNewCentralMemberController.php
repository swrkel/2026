<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewCentralMember;
use Modules\MembershipNew\app\Services\MembershipNewCentralMemberService;

class MembershipNewCentralMemberController extends Controller
{
    public function index(Request $request)
    {
        $records = MembershipNewCentralMember::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where('central_member_code', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('mobile', 'like', $search)
                    ->orWhere('nic', 'like', $search)
                    ->orWhere('email', 'like', $search);
            })
            ->latest()
            ->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        return view('membershipnew::central_members.index', compact('records'));
    }

    public function store(Request $request, MembershipNewCentralMemberService $service)
    {
        $central = $service->findOrCreateCentral($request->all());

        return redirect()->route('membership-new.central-members.index')
            ->with('status', 'Central member saved: ' . $central->central_member_code);
    }

    public function show(int $id)
    {
        $record = MembershipNewCentralMember::with('businessMaps')->findOrFail($id);

        return view('membershipnew::central_members.show', compact('record'));
    }
}
