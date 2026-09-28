<?php
namespace Modules\DealerManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\DistributionAvailabilityService;

class OperationsController extends Controller
{
    private function businessId(): int
    {
        $id = (int) (session('business.id') ?? session('business_id') ?? session('user.business_id') ?? 0);
        abort_unless($id > 0 && app(DistributionAvailabilityService::class)->enabledForBusiness($id), 404);
        return $id;
    }

    public function dashboard()
    {
        $businessId = $this->businessId();
        $stats = [
            'dealers' => DB::table('dlr_dealers')->where('business_id',$businessId)->count(),
            'outlets' => DB::table('dlr_outlets')->where('business_id',$businessId)->count(),
            'users' => DB::table('dlr_users')->where('business_id',$businessId)->where('is_active',1)->count(),
            'low_stock' => DB::table('dlr_stock_balances as b')
                ->leftJoin('dlr_reorder_rules as r', function($j){$j->on('r.dealer_id','=','b.dealer_id')->on('r.outlet_id','=','b.outlet_id')->on('r.product_id','=','b.product_id');})
                ->where('b.business_id',$businessId)
                ->whereRaw('COALESCE(b.confirmed_qty,b.system_qty,0) <= COALESCE(r.reorder_level,0)')
                ->count(),
            'pending_orders' => DB::table('dlr_orders')->where('business_id',$businessId)->whereIn('status',['draft','submitted','pending'])->count(),
            'unread_notifications' => DB::table('dlr_notifications')->where('business_id',$businessId)->where('is_read',0)->count(),
        ];
        return view('dealermanagement::admin.operations.dashboard', compact('stats'));
    }

    public function stock()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_stock_balances as b')
            ->leftJoin('dlr_dealers as d','d.id','=','b.dealer_id')
            ->leftJoin('dlr_outlets as o','o.id','=','b.outlet_id')
            ->select('b.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name')
            ->where('b.business_id',$businessId)->orderBy('d.name')->orderBy('b.product_name')->simplePaginate(50);
        return view('dealermanagement::admin.operations.stock',compact('rows'));
    }

    public function history()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_stock_movements as m')
            ->leftJoin('dlr_dealers as d','d.id','=','m.dealer_id')
            ->leftJoin('dlr_outlets as o','o.id','=','m.outlet_id')
            ->select('m.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name')
            ->where('m.business_id',$businessId)->orderByDesc('m.movement_at')->orderByDesc('m.id')->simplePaginate(50);
        return view('dealermanagement::admin.operations.history',compact('rows'));
    }

    public function reorder()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_stock_balances as b')
            ->leftJoin('dlr_dealers as d','d.id','=','b.dealer_id')
            ->leftJoin('dlr_outlets as o','o.id','=','b.outlet_id')
            ->leftJoin('dlr_reorder_rules as r', function($j){$j->on('r.dealer_id','=','b.dealer_id')->on('r.outlet_id','=','b.outlet_id')->on('r.product_id','=','b.product_id');})
            ->select('b.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name','r.min_qty','r.max_qty','r.reorder_level','r.safety_stock_qty','r.stock_cover_days','r.avg_daily_usage')
            ->where('b.business_id',$businessId)->orderBy('d.name')->orderBy('b.product_name')->simplePaginate(50);
        return view('dealermanagement::admin.operations.reorder',compact('rows'));
    }

    public function orders()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_orders as x')->leftJoin('dlr_dealers as d','d.id','=','x.dealer_id')->leftJoin('dlr_outlets as o','o.id','=','x.outlet_id')
            ->select('x.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name')->where('x.business_id',$businessId)->orderByDesc('x.id')->simplePaginate(50);
        return view('dealermanagement::admin.operations.orders',compact('rows'));
    }

    public function deliveries()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_stock_movements as m')->leftJoin('dlr_dealers as d','d.id','=','m.dealer_id')->leftJoin('dlr_outlets as o','o.id','=','m.outlet_id')
            ->select('m.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name')->where('m.business_id',$businessId)->where('m.movement_type','delivery')->orderByDesc('m.movement_at')->simplePaginate(50);
        return view('dealermanagement::admin.operations.deliveries',compact('rows'));
    }

    public function returns()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_stock_movements as m')->leftJoin('dlr_dealers as d','d.id','=','m.dealer_id')->leftJoin('dlr_outlets as o','o.id','=','m.outlet_id')
            ->select('m.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name')->where('m.business_id',$businessId)->whereIn('m.movement_type',['return','sales_return','dealer_return'])->orderByDesc('m.movement_at')->simplePaginate(50);
        return view('dealermanagement::admin.operations.returns',compact('rows'));
    }

    public function notifications()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_notifications as n')->leftJoin('dlr_dealers as d','d.id','=','n.dealer_id')->select('n.*','d.name as dealer_name','d.dealer_code')->where('n.business_id',$businessId)->orderByDesc('n.id')->simplePaginate(50);
        return view('dealermanagement::admin.operations.notifications',compact('rows'));
    }


    public function outlets()
    {
        $businessId = $this->businessId();
        $rows = DB::table('dlr_outlets as o')
            ->leftJoin('dlr_dealers as d','d.id','=','o.dealer_id')
            ->select('o.*','d.name as dealer_name','d.dealer_code')
            ->where('o.business_id',$businessId)
            ->orderBy('d.name')->orderBy('o.name')->simplePaginate(50);
        return view('dealermanagement::admin.operations.outlets',compact('rows'));
    }

    public function users()
    {
        $businessId = $this->businessId();
        $rows = DB::table('dlr_users as u')
            ->leftJoin('dlr_dealers as d','d.id','=','u.dealer_id')
            ->leftJoin('dlr_roles as r','r.id','=','u.role_id')
            ->select('u.*','d.name as dealer_name','d.dealer_code','r.name as role_name')
            ->where('u.business_id',$businessId)
            ->orderBy('d.name')->orderBy('u.name')->simplePaginate(50);
        return view('dealermanagement::admin.operations.users',compact('rows'));
    }

    public function roles()
    {
        $businessId = $this->businessId();
        $rows = DB::table('dlr_roles as r')
            ->leftJoin('dlr_dealers as d','d.id','=','r.dealer_id')
            ->leftJoin('dlr_role_permissions as p','p.role_id','=','r.id')
            ->select('r.id','r.business_id','r.dealer_id','r.name','r.is_system','r.notes','r.created_at','d.name as dealer_name','d.dealer_code',DB::raw('COUNT(p.id) as permissions_count'))
            ->where('r.business_id',$businessId)
            ->groupBy('r.id','r.business_id','r.dealer_id','r.name','r.is_system','r.notes','r.created_at','d.name','d.dealer_code')
            ->orderBy('d.name')->orderBy('r.name')->simplePaginate(50);
        return view('dealermanagement::admin.operations.roles',compact('rows'));
    }

    public function stockUpdates()
    {
        $businessId = $this->businessId();
        $rows = DB::table('dlr_stock_updates as x')
            ->leftJoin('dlr_dealers as d','d.id','=','x.dealer_id')
            ->leftJoin('dlr_outlets as o','o.id','=','x.outlet_id')
            ->leftJoin('dlr_users as u','u.id','=','x.submitted_by')
            ->leftJoin('dlr_stock_update_lines as l','l.stock_update_id','=','x.id')
            ->select('x.id','x.business_id','x.dealer_id','x.outlet_id','x.update_no','x.update_date','x.notes','x.submitted_by','x.submitted_at','d.name as dealer_name','d.dealer_code','o.name as outlet_name','u.name as submitted_by_name',DB::raw('COUNT(l.id) as product_lines'),DB::raw('COALESCE(SUM(ABS(l.variance_qty)),0) as total_absolute_variance'))
            ->where('x.business_id',$businessId)
            ->groupBy('x.id','x.business_id','x.dealer_id','x.outlet_id','x.update_no','x.update_date','x.notes','x.submitted_by','x.submitted_at','d.name','d.dealer_code','o.name','u.name')
            ->orderByDesc('x.submitted_at')->orderByDesc('x.id')->simplePaginate(50);
        return view('dealermanagement::admin.operations.stock-updates',compact('rows'));
    }

    public function reports()
    {
        $businessId=$this->businessId();
        $lowStock=DB::table('dlr_stock_balances as b')->leftJoin('dlr_dealers as d','d.id','=','b.dealer_id')->leftJoin('dlr_outlets as o','o.id','=','b.outlet_id')
            ->leftJoin('dlr_reorder_rules as r', function($j){$j->on('r.dealer_id','=','b.dealer_id')->on('r.outlet_id','=','b.outlet_id')->on('r.product_id','=','b.product_id');})
            ->select('b.*','d.name as dealer_name','o.name as outlet_name','r.reorder_level')
            ->where('b.business_id',$businessId)->whereRaw('COALESCE(b.confirmed_qty,b.system_qty,0) <= COALESCE(r.reorder_level,0)')->orderBy('d.name')->limit(100)->get();
        return view('dealermanagement::admin.operations.reports',compact('lowStock'));
    }
    public function hubSales()
    {
        $businessId=$this->businessId();
        $rows=DB::table('dlr_stock_movements as m')
            ->leftJoin('dlr_dealers as d','d.id','=','m.dealer_id')
            ->leftJoin('dlr_outlets as o','o.id','=','m.outlet_id')
            ->select('m.*','d.name as dealer_name','d.dealer_code','o.name as outlet_name')
            ->where('m.business_id',$businessId)->where('m.movement_type','hub_dealer_sale')
            ->orderByDesc('m.movement_at')->orderByDesc('m.id')->simplePaginate(50);
        return view('dealermanagement::admin.operations.hub-sales',compact('rows'));
    }

}
