<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Admin;
use Modules\PumperDashboardNew\Entities\PonePrintLog;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
class PrintLogController extends Controller
{
    public function index(){ $logs=PonePrintLog::query()->where('business_id',$this->businessId())->when(request('printable_type'),fn($q)=>$q->where('printable_type',request('printable_type')))->latest('printed_at')->paginate(100);return view('pumperdashboardnew::admin.print-logs.index',compact('logs'));}
}
