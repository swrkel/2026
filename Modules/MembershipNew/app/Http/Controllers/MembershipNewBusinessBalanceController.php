<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Services\MembershipNewBusinessBalanceService;

class MembershipNewBusinessBalanceController extends Controller
{
    public function index(Request $request, MembershipNewBusinessBalanceService $service)
    {
        $perPage = in_array((int) $request->input('per_page', 50), [10, 25, 50, 100, 200], true) ? (int) $request->input('per_page', 50) : 50;
        $records = $service->balances(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->input('q'))->paginate($perPage)->appends($request->query());
        return view('membershipnew::business_balances.index', compact('records'));
    }
}
