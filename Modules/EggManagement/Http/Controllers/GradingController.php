<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Collection;use Modules\EggManagement\Models\Grade;use Modules\EggManagement\Models\GradingRun;use Modules\EggManagement\Services\GradingService;
class GradingController extends BaseController
{
    public function index(){return view('egg::grading.index',['rows'=>$this->scope(GradingRun::query())->latest('graded_on')->latest('id')->paginate(50)]);}
    public function create(){return view('egg::grading.create',['collections'=>$this->scope(Collection::query())->whereIn('status',['open','partially_graded'])->orderBy('collection_date')->get(),'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get()]);}
    public function store(Request $r,GradingService $svc){$d=$r->validate(['collection_id'=>'required|integer','graded_on'=>'required|date','note'=>'nullable|max:1000','lines'=>'required|array','lines.*.grade_id'=>'required|integer','lines.*.pieces'=>'required|integer|min:0','lines.*.unit_cost'=>'nullable|numeric|min:0','lines.*.best_before'=>'nullable|date']);$svc->create($d);return redirect()->route('egg.grading.index')->with('success','Grading posted and stock updated.');}
}
