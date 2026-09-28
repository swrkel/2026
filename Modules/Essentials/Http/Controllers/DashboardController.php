<?php

namespace Modules\Essentials\Http\Controllers;

use App\Category;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsHoliday;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;
use Modules\Essentials\Utils\EssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $essentialsUtil;

    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(
        ModuleUtil $moduleUtil,
        EssentialsUtil $essentialsUtil,
        TransactionUtil $transactionUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function hrmDashboard()
    {
        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        $user_id = auth()->user()->id;

        $users = User::where('business_id', $business_id)
            // ->user()
            ->get();

        $departments = Category::where('business_id', $business_id)
            ->where('category_type', 'hrm_department')
            ->get();
        $users_by_dept = $users->groupBy('essentials_department_id');

        $today = new \Carbon('today');

        $one_month_from_today = \Carbon::now()->addMonth();
        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->with(['user', 'leave_type'])
            ->orderBy('start_date', 'asc')
            ->get();

        $todays_leaves = [];
        $upcoming_leaves = [];

        $users_leaves = [];
        foreach ($leaves as $leave) {
            $leave_start = \Carbon::parse($leave->start_date);
            $leave_end = \Carbon::parse($leave->end_date);

            if ($today->gte($leave_start) && $today->lte($leave_end)) {
                $todays_leaves[] = $leave;

                if ($leave->user_id == $user_id) {
                    $users_leaves[] = $leave;
                }
            } elseif ($today->lt($leave_start) && $leave_start->lte($one_month_from_today)) {
                $upcoming_leaves[] = $leave;

                if ($leave->user_id == $user_id) {
                    $users_leaves[] = $leave;
                }
            }
        }

        $holidays_query = EssentialsHoliday::where(
            'essentials_holidays.business_id',
            $business_id
        )
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->orderBy('start_date', 'asc')
            ->with(['location']);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $holidays_query->where(function ($query) use ($permitted_locations) {
                $query->whereIn('essentials_holidays.location_id', $permitted_locations)
                    ->orWhereNull('essentials_holidays.location_id');
            });
        }
        $holidays = $holidays_query->get();

        $todays_holidays = [];
        $upcoming_holidays = [];

        foreach ($holidays as $holiday) {
            $holiday_start = \Carbon::parse($holiday->start_date);
            $holiday_end = \Carbon::parse($holiday->end_date);

            if ($today->gte($holiday_start) && $today->lte($holiday_end)) {
                $todays_holidays[] = $holiday;
            } elseif ($today->lt($holiday_start) && $holiday_start->lte($one_month_from_today)) {
                $upcoming_holidays[] = $holiday;
            }
        }

        $todays_attendances = [];
        if ($is_admin) {
            $todays_attendances = EssentialsAttendance::where('business_id', $business_id)
                ->whereDate('clock_in_time', \Carbon::now()->format('Y-m-d'))
                ->with(['employee'])
                ->orderBy('clock_in_time', 'asc')
                ->get();
        }

        $settings = $this->essentialsUtil->getEssentialsSettings();

        $sales_targets = EssentialsUserSalesTarget::where('user_id', $user_id)
            ->get();

        $start_date = \Carbon::today()->startOfMonth()->format('Y-m-d');
        $end_date = \Carbon::today()->endOfMonth()->format('Y-m-d');

        $sale_totals = $this->transactionUtil->getUserTotalSales($business_id, $user_id, $start_date, $end_date);

        $target_achieved_this_month = !empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1 ? $sale_totals['total_sales_without_tax'] : $sale_totals['total_sales'];

        $start_date = \Carbon::parse('first day of last month')->format('Y-m-d');
        $end_date = \Carbon::parse('last day of last month')->format('Y-m-d');

        $sale_totals = $this->transactionUtil->getUserTotalSales($business_id, $user_id, $start_date, $end_date);

        $target_achieved_last_month = !empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1 ? $sale_totals['total_sales_without_tax'] : $sale_totals['total_sales'];

        // Get the current date and time
        $now = \Carbon::now()->addDays(1)->format('Y-m-d');

        // Calculate the date 30 days from now
        $thirtyDaysFromNow = \Carbon::now()->addDays(30)->format('Y-m-d');

        // Retrieve all users with a date of birth within the next 30 days
        $up_comming_births = User::whereRaw("DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT('$now', '%m-%d') AND DATE_FORMAT('$thirtyDaysFromNow', '%m-%d')")->orderBy('dob', 'asc')->get();
        
        $today_births = User::whereMonth('dob', \Carbon::now()->format('m'))
        ->whereDay('dob', \Carbon::now()->format('d'))
        ->get();

        return view('essentials::dashboard.hrm_dashboard')
            ->with(compact('users', 'departments', 'users_by_dept', 'todays_holidays', 'todays_leaves', 'upcoming_leaves', 'is_admin', 'users_leaves', 'upcoming_holidays', 'todays_attendances', 'sales_targets', 'target_achieved_this_month', 'target_achieved_last_month', 'up_comming_births', 'today_births'));
    }

    public function getUserSalesTargets()
    {
        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        $user_id = auth()->user()->id;

        if (!$is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $this_month_start_date = \Carbon::today()->startOfMonth()->format('Y-m-d');
        $this_month_end_date = \Carbon::today()->endOfMonth()->format('Y-m-d');
        $last_month_start_date = \Carbon::parse('first day of last month')->format('Y-m-d');
        $last_month_end_date = \Carbon::parse('last day of last month')->format('Y-m-d');

        $settings = $this->essentialsUtil->getEssentialsSettings();

        $last_month_start_at = $last_month_start_date . ' 00:00:00';
        $last_month_end_at = $last_month_end_date . ' 23:59:59';
        $this_month_start_at = $this_month_start_date . ' 00:00:00';
        $this_month_end_at = $this_month_end_date . ' 23:59:59';

        $query = User::where('users.business_id', $business_id)
            ->join('transactions as t', 't.commission_agent', '=', 'users.id')
            ->where('t.type', 'sell')
            ->where('t.transaction_date', '>=', $last_month_start_at)
            ->where('t.status', 'final');

        if (!empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1) {
            $sell_line_tax_totals = DB::table('transaction_sell_lines as target_tsl')
                ->select('target_tsl.transaction_id')
                ->selectRaw('SUM(target_tsl.item_tax * target_tsl.quantity) as total_line_tax')
                ->groupBy('target_tsl.transaction_id');

            $query->leftJoinSub($sell_line_tax_totals, 'target_tsl_tax', function ($join) {
                $join->on('target_tsl_tax.transaction_id', '=', 't.id');
            });

            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name")
            )
                ->selectRaw(
                    'SUM(IF(t.transaction_date BETWEEN ? AND ?, t.total_before_tax - t.shipping_charges - target_tsl_tax.total_line_tax, 0)) as total_sales_last_month',
                    [$last_month_start_at, $last_month_end_at]
                )
                ->selectRaw(
                    'SUM(IF(t.transaction_date BETWEEN ? AND ?, t.total_before_tax - t.shipping_charges - target_tsl_tax.total_line_tax, 0)) as total_sales_this_month',
                    [$this_month_start_at, $this_month_end_at]
                );
        } else {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name")
            )
                ->selectRaw(
                    'SUM(IF(t.transaction_date BETWEEN ? AND ?, t.final_total, 0)) as total_sales_last_month',
                    [$last_month_start_at, $last_month_end_at]
                )
                ->selectRaw(
                    'SUM(IF(t.transaction_date BETWEEN ? AND ?, t.final_total, 0)) as total_sales_this_month',
                    [$this_month_start_at, $this_month_end_at]
                );
        }

        $query->groupBy('users.id');
        
        $query = $query->get();

        return Datatables::of($query)
            ->editColumn('total_sales_this_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_this_month, true);
            })
            ->editColumn('total_sales_last_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_last_month, true);
            })
            ->make(false);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function essentialsDashboard()
    {
        return view('essentials::dashboard.essentials_dashboard');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('essentials::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('essentials::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('essentials::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
