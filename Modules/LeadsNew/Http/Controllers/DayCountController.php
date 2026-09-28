<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\LeadsNew\Entities\Lead;
use Yajra\DataTables\Facades\DataTables;

class DayCountController extends Controller
{
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil =  $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('business.id');
        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'day_count')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $leads = Lead::leftjoin('users', 'leads_new.created_by', 'users.id')
                ->where('leads_new.business_id', $business_id)
                ->select([
                    'leads_new.date',
                    'users.username as user',
                    DB::raw('COUNT(leads_new.id) as day_count ')
                ])->groupBy('leads_new.date');
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $leads->whereDate('date', '>=', request()->start_date);
                $leads->whereDate('date', '<=', request()->end_date);
            }
            if (!empty(request()->created_by)) {
                $leads->where('leads_new.created_by', request()->created_by);
            }

            return DataTables::of($leads)
                ->editColumn('date', '{{@format_date($date)}}')
                ->removeColumn('id')
                ->rawColumns(['action', 'mass_delete'])
                ->make(true);
        }
        $users = User::where('users.business_id', $business_id)->pluck('username', 'users.id');

        return view('leadsnew::day_count.index')->with(compact(
            'users'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('leadsnew::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('leadsnew::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('leadsnew::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}
