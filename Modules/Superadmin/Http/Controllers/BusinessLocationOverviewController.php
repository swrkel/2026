<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Services\BusinessLocationAccessService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class BusinessLocationOverviewController extends Controller
{
    public function index(Request $request, BusinessLocationAccessService $locationAccess)
    {
        abort_unless($locationAccess->isCentralSuperAdmin(), 403);

        $administrativeBusinessIds = $locationAccess->administrativeBusinessIds();
        abort_unless($administrativeBusinessIds, 403);

        $query = DB::table('business_locations as bl')
            ->join('business as b', 'b.id', '=', 'bl.business_id')
            ->whereNotIn('bl.business_id', $administrativeBusinessIds)
            ->select([
                'bl.id',
                'bl.business_id',
                'bl.location_id',
                'bl.name as location_name',
                'bl.address_1',
                'bl.address_2',
                'bl.city',
                'bl.state',
                'bl.country',
                'bl.mobile',
                'bl.email',
                'bl.is_active',
                'b.name as business_name',
            ])
            ->when($request->filled('business_id'), function ($locationQuery) use ($request) {
                $locationQuery->where('bl.business_id', (int) $request->business_id);
            })
            ->when($request->filled('status'), function ($locationQuery) use ($request) {
                $locationQuery->where('bl.is_active', $request->status === 'active' ? 1 : 0);
            })
            ->when($request->filled('search'), function ($locationQuery) use ($request) {
                $search = '%' . trim((string) $request->search) . '%';
                $locationQuery->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('b.name', 'like', $search)
                        ->orWhere('bl.name', 'like', $search)
                        ->orWhere('bl.location_id', 'like', $search)
                        ->orWhere('bl.city', 'like', $search)
                        ->orWhere('bl.mobile', 'like', $search);
                });
            })
            ->orderBy('b.name')
            ->orderBy('bl.name');

        $locations = $query->paginate(50)->appends($request->query());
        $businesses = DB::table('business')
            ->whereNotIn('id', $administrativeBusinessIds)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('superadmin::business_locations.overview', compact('locations', 'businesses'));
    }
}
