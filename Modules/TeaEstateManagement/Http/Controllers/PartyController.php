<?php
namespace Modules\TeaEstateManagement\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\AuditService;
class PartyController extends BaseTeaController
{
    public function index(){ $d=$this->common(); $d['parties']=$d['installed']?DB::table('tea_parties')->where('business_id',$this->businessId())->orderByDesc('id')->limit(300)->get():collect(); return view('teaestate::parties.index',$d); }
    public function store(Request $r,AuditService $audit){ $r->validate(['party_type'=>'required|in:supplier,buyer,both','name'=>'required|string|max:180']); $b=$this->businessId(); $code=$r->party_code?:'TP-'.str_pad((string)(DB::table('tea_parties')->where('business_id',$b)->count()+1),5,'0',STR_PAD_LEFT); $id=DB::table('tea_parties')->insertGetId(['business_id'=>$b,'party_code'=>$code,'party_type'=>$r->party_type,'name'=>$r->name,'mobile'=>$r->mobile,'email'=>$r->email,'address'=>$r->address,'tax_no'=>$r->tax_no,'status'=>'active','notes'=>$r->notes,'created_at'=>now(),'updated_at'=>now()]); $audit->log('create','party',$id); return back()->with('tea_success','Tea party created.'); }
}
