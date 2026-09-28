<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Http\Request;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
class PurchaseReportController extends BaseReportController
{
    public function index(Request $r,EggContext $context,LocationStoreGateway $directory){$data=$this->reportData($r,'egg_purchases','purchase_date',$context,$directory,['purchase_no','purchase_date','supplier_id','subtotal','discount','total','payment_status']);return view('egg::reports.purchases',$data);}
}
