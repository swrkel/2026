<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\{LawMatter,LawClient,LawPracticeArea,LawCourt}; use Modules\EzyLaw\Services\MatterService;
class MatterController extends Controller
{
 public function index(Request $r){$q=LawMatter::with(['client','practiceArea','court']); if($r->filled('q')){$x=$r->q;$q->where(fn($z)=>$z->where('matter_no','like','%'.$x.'%')->orWhere('case_no','like','%'.$x.'%')->orWhere('title','like','%'.$x.'%'));} if($r->filled('status'))$q->where('status',$r->status); return view('ezylaw::matters.index',['matters'=>$q->orderByDesc('id')->paginate(25)->appends($r->query())]);}
 public function create(){return view('ezylaw::matters.form',$this->formData(new LawMatter));}
 public function store(Request $r,MatterService $s){$m=$s->store($this->validateData($r)); return redirect()->route('ezylaw.matters.show',$m)->with('success','Matter saved successfully.');}
 public function show(LawMatter $matter){$matter->load(['client','practiceArea','court','hearings','tasks','timeEntries','parties','chronology','retainers','reminders','communications']); return view('ezylaw::matters.show',compact('matter'));}
 public function edit(LawMatter $matter){return view('ezylaw::matters.form',$this->formData($matter));}
 public function update(Request $r,LawMatter $matter,MatterService $s){$s->update($matter,$this->validateData($r)); return redirect()->route('ezylaw.matters.show',$matter)->with('success','Matter updated successfully.');}
 public function destroy(LawMatter $matter){$matter->delete(); return redirect()->route('ezylaw.matters.index')->with('success','Matter deleted.');}
 private function formData($matter):array{return ['matter'=>$matter,'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'areas'=>LawPracticeArea::where('active',1)->orderBy('name')->get(),'courts'=>LawCourt::where('active',1)->orderBy('name')->get()];}
 private function validateData(Request $r):array{return $r->validate(['matter_no'=>'nullable|string|max:50','client_id'=>'required|integer','practice_area_id'=>'nullable|integer','court_id'=>'nullable|integer','location_id'=>'nullable|integer','case_no'=>'nullable|string|max:100','title'=>'required|string|max:191','description'=>'nullable|string','status'=>'required|in:open,pending,closed,archived','priority'=>'required|in:low,normal,high,urgent','opened_on'=>'required|date','closed_on'=>'nullable|date','assigned_user_id'=>'nullable|integer','responsible_lawyer_id'=>'nullable|integer','opposing_party'=>'nullable|string|max:191','opposing_counsel'=>'nullable|string|max:191','estimated_value'=>'nullable|numeric|min:0','fee_type'=>'required|in:hourly,fixed,retainer,contingency,other','hourly_rate'=>'nullable|numeric|min:0','fixed_fee'=>'nullable|numeric|min:0','retainer_amount'=>'nullable|numeric|min:0']);}
}
