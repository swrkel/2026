<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\DealerContext;
use Modules\DealerManagement\Services\ReorderService;

class DashboardController extends Controller
{
    public function index()
    {
        $ctx=app(DealerContext::class); $user=$ctx->user(); $outletIds=$ctx->outletIds();
        $dealer=DB::table('dlr_dealers')->where('id',$user->dealer_id)->first();
        $stockCount=DB::table('dlr_stock_balances')->where('dealer_id',$user->dealer_id)->whereIn('outlet_id',$outletIds ?: [0])->count();
        $notifications=DB::table('dlr_notifications')->where('dealer_id',$user->dealer_id)->where('is_read',0)->count();
        $pendingOrders=DB::table('dlr_orders')->where('dealer_id',$user->dealer_id)->whereIn('status',['draft','submitted'])->count();
        $reorders=app(ReorderService::class)->reorderCount($user->business_id,$user->dealer_id,$outletIds);
        $buttons=[
            ['permission'=>'stock.view','title'=>'My Stock','route'=>'dealermanagement.portal.stock.index','icon'=>'fa-cubes'],
            ['permission'=>'stock.update','title'=>'Update Stock','route'=>'dealermanagement.portal.stock.update.form','icon'=>'fa-check-square-o'],
            ['permission'=>'orders.view','title'=>'Orders / Requests','route'=>'dealermanagement.portal.orders.index','icon'=>'fa-shopping-cart'],
            ['permission'=>'deliveries.view','title'=>'My Deliveries','route'=>'dealermanagement.portal.deliveries.index','icon'=>'fa-truck'],
            ['permission'=>'returns.view','title'=>'Returns','route'=>'dealermanagement.portal.returns.index','icon'=>'fa-undo'],
            ['permission'=>'notifications.view','title'=>'Notifications','route'=>'dealermanagement.portal.notifications.index','icon'=>'fa-bell'],
            ['permission'=>'reports.view','title'=>'My Reports','route'=>'dealermanagement.portal.reports.index','icon'=>'fa-bar-chart'],
            ['permission'=>'users.view','title'=>'My Staff / Users','route'=>'dealermanagement.portal.users.index','icon'=>'fa-users'],
            ['permission'=>'users.manage','title'=>'Roles & Permissions','route'=>'dealermanagement.portal.roles.index','icon'=>'fa-key'],
            ['permission'=>'settings.manage','title'=>'Re-order Rules','route'=>'dealermanagement.portal.reorder.index','icon'=>'fa-bell-o'],
            ['permission'=>'outlets.view','title'=>'Dealer Outlets','route'=>'dealermanagement.portal.outlets.index','icon'=>'fa-building'],
        ];
        $buttons=array_values(array_filter($buttons,fn($b)=>$ctx->can($b['permission'])));
        return view('dealermanagement::dashboard.index',compact('user','dealer','buttons','stockCount','notifications','pendingOrders','reorders'));
    }
}
