<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\{LawTimeEntry,LawMatter}; use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class TimeEntryController extends Controller
{
 public function index(){return view('ezylaw::time.index',['entries'=>LawTimeEntry::with('matter')->orderByDesc('work_date')->paginate(25),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get()]);}
 public function store(Request $r){$d=$r->validate(['matter_id'=>'required|integer','work_date'=>'required|date','minutes'=>'required|integer|min:1','rate'=>'required|numeric|min:0','billable'=>'nullable|boolean','description'=>'required|string|max:1000']);EzyLawReferenceGuard::matter($d['matter_id']);$d['amount']=round(($d['minutes']/60)*(float)$d['rate'],4);$d['billable']=$r->boolean('billable');$d['invoiced']=0;$d['user_id']=auth()->id();LawTimeEntry::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]);return back()->with('success','Time entry saved.');}
}
