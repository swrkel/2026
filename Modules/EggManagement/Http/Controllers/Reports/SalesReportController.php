<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Http\Request;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
class SalesReportController extends BaseReportController
{
    public function index(Request $r,EggContext $context,LocationStoreGateway $directory){$data=$this->reportData($r,'egg_sales','sale_date',$context,$directory,['sale_no','sale_date','customer_id','subtotal','discount','total','payment_status']);return view('egg::reports.sales',$data);}
}
