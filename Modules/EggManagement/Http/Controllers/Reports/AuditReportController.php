<?php
namespace Modules\EggManagement\Http\Controllers\Reports;
use Illuminate\Http\Request;use Modules\EggManagement\Services\EggContext;use Modules\EggManagement\Integrations\LocationStoreGateway;
class AuditReportController extends BaseReportController
{
    public function index(Request $r,EggContext $context,LocationStoreGateway $directory){$data=$this->reportData($r,'egg_audit_logs','created_at',$context,$directory,['created_at','user_id','action','auditable_type','auditable_id','ip_address']);return view('egg::reports.audit',$data);}
}
