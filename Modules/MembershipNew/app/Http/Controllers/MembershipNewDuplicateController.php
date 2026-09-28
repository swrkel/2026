<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewDuplicateCandidate;
use Modules\MembershipNew\app\Services\MembershipNewDuplicateService;

class MembershipNewDuplicateController extends Controller
{
    public function index()
    {
        $records = MembershipNewDuplicateCandidate::latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        return view('membershipnew::central_members.duplicates', compact('records'));
    }

    public function scan(MembershipNewDuplicateService $service)
    {
        $count = $service->scan();

        return back()->with('status', $count . ' duplicate candidate checks prepared.');
    }

    public function resolve(Request $request, int $candidateId, MembershipNewDuplicateService $service)
    {
        $service->markResolved($candidateId, $request->resolution_note);

        return back()->with('status', 'Duplicate candidate marked as resolved.');
    }
}
