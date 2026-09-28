<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Http\Request;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
class StockReportController extends BaseReportController
{
    public function index(Request $r,EggContext $context,LocationStoreGateway $directory){$data=$this->reportData($r,'egg_stock_lots','collection_date',$context,$directory,['lot_no','collection_date','grade_id','received_pieces','available_pieces','unit_cost','status']);return view('egg::reports.stock',$data);}
}
