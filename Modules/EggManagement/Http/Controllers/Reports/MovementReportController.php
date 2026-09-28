<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Http\Request;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
class MovementReportController extends BaseReportController
{
    public function index(Request $r,EggContext $context,LocationStoreGateway $directory){$data=$this->reportData($r,'egg_stock_movements','movement_date',$context,$directory,['movement_date','direction','grade_id','pieces','unit_cost','source_type','source_id']);return view('egg::reports.movements',$data);}
}
