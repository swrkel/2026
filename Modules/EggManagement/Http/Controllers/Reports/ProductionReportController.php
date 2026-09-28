<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Http\Request;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
class ProductionReportController extends BaseReportController
{
    public function index(Request $r,EggContext $context,LocationStoreGateway $directory){$data=$this->reportData($r,'egg_collections','collection_date',$context,$directory,['collection_no','collection_date','total_pieces','good_pieces','broken_pieces','dirty_pieces','rejected_pieces','status']);return view('egg::reports.production',$data);}
}
