<?php
namespace Modules\LeadsNew\Http\Controllers;
use Illuminate\Routing\Controller; use Modules\LeadsNew\Models\LeadsNewLead; use Modules\LeadsNew\Models\LeadsNewCampaign;
class LeadsNewAnalyticsController extends Controller
{
    public function executive(){ $stats=['total'=>LeadsNewLead::count(),'converted'=>LeadsNewLead::where('status','Converted')->count(),'lost'=>LeadsNewLead::where('status','Lost')->count(),'campaigns'=>LeadsNewCampaign::count()]; return view('leadsnew::reports.executive',compact('stats')); }
    public function source(){ $rows=LeadsNewLead::selectRaw('source, count(*) as total')->groupBy('source')->orderByDesc('total')->get(); return view('leadsnew::reports.source',compact('rows')); }
}
