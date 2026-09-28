<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Modules\DealerManagement\Services\{HubContext,HubDatabaseManager};
class HubReportController extends Controller
{
 public function index(Request $r){$u=app(HubContext::class)->user();$from=$r->get('from',now()->startOfMonth()->toDateString());$to=$r->get('to',now()->toDateString());$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_sales_allocations as a')->join('dlr_hub_sales_entry_lines as l','l.id','=','a.sales_entry_line_id')->join('dlr_hub_sales_entries as e','e.id','=','l.sales_entry_id')->join('dlr_hub_distributors as d','d.id','=','a.distributor_id')->where('e.hub_dealer_id',$u->hub_dealer_id)->whereBetween('e.sale_date',[$from,$to])->select('d.name as distributor_name','l.product_name',DB::raw('SUM(a.allocated_sold_qty) sold_qty'),DB::raw('SUM(a.allocated_return_qty) return_qty'),DB::raw('SUM(a.allocated_damage_qty) damage_qty'))->groupBy('d.name','l.product_name')->orderBy('d.name')->orderBy('l.product_name')->get());return view('dealermanagement::hub.reports.index',compact('rows','from','to'));}
}
